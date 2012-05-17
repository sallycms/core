<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_AddOn_Manager {
	protected $addOnService; ///< sly_Service_AddOn
	protected $pkgService;   ///< sly_Service_Package
	protected $loadInfo;     ///< array
	protected $loaded;       ///< array

	/**
	 * @param sly_Service_AddOn $service
	 */
	public function __construct(sly_Service_AddOn $service) {
		$this->addOnService = $service;
		$this->pkgService   = $service->getPackageService();
		$this->loadInfo     = array();
		$this->loaded       = array();
	}

	/**
	 * Copy assets from addon to it's public folder
	 *
	 * This method copies all files in 'assets' to the public directory of the
	 * given addon.
	 *
	 * @param  string $addon  addon name
	 * @return mixed          true if successful, else an error message as a string
	 */
	public function copyAssets($addon) {
		$baseDir   = $this->pkgService->baseDirectory($addon);
		$target    = $this->addOnService->publicDirectory($addon);
		$assetsDir = $baseDir.'assets';

		if (!is_dir($assetsDir)) {
			return true;
		}

		$dir = new sly_Util_Directory($assetsDir);

		if (!$dir->copyTo($target)) {
			return t('addon_assets_failed', $assetsDir);
		}

		return true;
	}

	/**
	 * Adds a new addon to the global config
	 *
	 * @param string $addon  addon name
	 */
	public function add($addon) {
		$this->addOnService->setProperty($addon, 'install', false);
		$this->addOnService->setProperty($addon, 'status', false);
		$this->clearCache();
	}

	/**
	 * Removes a addon from the global config
	 *
	 * @param string $addon  addon name
	 */
	public function remove($addon) {
		$path = $this->addOnService->getConfPath($addon);
		sly_Core::config()->remove($path);
		$this->clearCache();
	}

	/**
	 * Removes all public files
	 *
	 * @param  string $addon  addon name
	 * @return mixed          true if successful, else an error message as a string
	 */
	public function deletePublicFiles($addon) {
		return $this->deleteFiles('public', $addon);
	}

	/**
	 * Removes all internal files
	 *
	 * @param  string $addon  addon name
	 * @return mixed          true if successful, else an error message as a string
	 */
	public function deleteInternalFiles($addon) {
		return $this->deleteFiles('internal', $addon);
	}

	/**
	 * Removes all files in a directory
	 *
	 * @param  string $type   'public' or 'internal'
	 * @param  string $addon  addon name
	 * @return mixed          true if successful, else an error message as a string
	 */
	protected function deleteFiles($type, $addon) {
		$dir   = $this->addOnService->dynDirectory($type, $addon);
		$state = $this->fireEvent('PRE', 'DELETE_'.strtoupper($type), $addon, true);

		if ($state !== true) {
			return $state;
		}

		$obj = new sly_Util_Directory($dir);

		if (!$obj->delete(true)) {
			return t('addon_cleanup_failed', $dir);
		}

		return $this->fireEvent('POST', 'DELETE_'.strtoupper($type), $addon, false);
	}

	/**
	 * Include file
	 *
	 * This prevents the included file from messing with the variables of the
	 * surrounding code.
	 *
	 * @param string $filename
	 */
	protected function req($filename) {
		require $filename;
	}

	/**
	 * Check if an addOn has changed and must be disabled
	 *
	 * @return boolean  true if there were changes, else false
	 */
	public function deactivateIncompatible() {
		$service  = $this->addOnService;
		$pservice = $this->pkgService;
		$addons   = $service->getAvailableAddOns();
		$changes  = false;

		foreach ($addons as $addon) {
			$compatible = $service->isCompatible($addon);
			if ($compatible && $pservice->exists($addon)) continue;

			// disable all dependencies
			$deps = $pservice->getDependencies($addon);

			foreach ($deps as $dep) {
				$service->setProperty($dep, 'status', false);
			}

			// disable addon itself
			$service->setProperty($addon, 'status', false);

			// remember this
			$changes = true;
		}

		if ($changes) {
			$this->clearCache();
		}

		return $changes;
	}

	/**
	 * Install an addOn
	 *
	 * @param  string  $addon        addOn name
	 * @param  boolean $installDump
	 * @return mixed                 message or true if successful
	 */
	public function install($addon, $installDump = true) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$baseDir  = $pservice->baseDirectory($addon);
		$bootFile = $baseDir.'boot.php';

		// return error message if an addon wants to stop the install process
		$state = $this->fireEvent('PRE', 'INSTALL', $addon, true);
		if ($state !== true) return $state;

		// check for boot.php before we do anything
		if (!is_readable($bootFile)) {
			return t('addon_boot_file_not_found', $addon);
		}

		// check requirements
		$msg = $this->checkRequirements($addon);
		if ($msg !== true) return $msg;

		// check Sally version
		$sallyVersions = $aservice->getRequiredSallyVersions($addon);

		if (!empty($sallyVersions)) {
			if (!$aservice->isCompatible($addon)) {
				return t('addon_incompatible', $addon, sly_Core::getVersion('X.Y.Z'));
			}
		}
		else {
			return t('addon_has_no_sally_version_info', $addon);
		}

		// include install.php if available
		$installFile = $baseDir.'install.php';

		if (is_readable($installFile)) {
			try {
				$this->req($installFile);
			}
			catch (Exception $e) {
				return t('addon_install_failed', $addon, $e->getMessage());
			}
		}

		// read install.sql and install DB
		$installSQL = $baseDir.'install.sql';

		if ($installDump && is_readable($installSQL)) {
			$mysiam = $pservice->getKey($addon, 'allow_non_innodb', false);
			$state  = $this->installDump($installSQL, $mysiam);

			if ($state !== true) {
				return t('addon_install_sql_failed', $addon, $state);
			}
		}

		// copy assets to data/dyn/public
		$this->copyAssets($addon);

		// load globals.yml
		$this->loadConfig($addon, false, true);

		// mark addOn as installed
		$aservice->setProperty($addon, 'install', true);

		// store current addOn version
		$version = $pservice->getVersion($addon);

		if ($version !== null) {
			$pservice->setKnownVersion($addon, $version);
		}

		// notify listeners
		return $this->fireEvent('POST', 'INSTALL', $addon, false);
	}

	/**
	 * Uninstall an addOn
	 *
	 * @param  string $addon  addOn name
	 * @return mixed          message or true if successful
	 */
	public function uninstall($addon) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// if not installed, try to disable if needed
		if (!$aservice->isInstalled($addon)) {
			return $this->deactivate($addon);
		}

		// check for dependencies
		$state = $this->checkDependencies($addon);
		if ($state !== true) return $state;

		// stop if (another) addon forbids uninstall
		$state = $this->fireEvent('PRE', 'UNINSTALL', $addon, true);
		if ($state !== true) return $state;

		// deactivate addon first
		$state = $this->deactivate($addon);
		if ($state !== true) return $state;

		// include uninstall.php if available
		$baseDir   = $pservice->baseDirectory($addon);
		$uninstall = $baseDir.'uninstall.php';

		if (is_readable($uninstall)) {
			try {
				$this->req($uninstall);
			}
			catch (Exception $e) {
				return t('addon_uninstall_failed', $addon, $e->getMessage());
			}
		}

		// read uninstall.sql
		$uninstallSQL = $baseDir.'uninstall.sql';

		if (is_readable($uninstallSQL)) {
			$state = $this->installDump($uninstallSQL);

			if ($state !== true) {
				return t('addon_uninstall_sql_failed', $addon, $state);
			}
		}

		// mark addOn as not installed
		$aservice->setProperty($addon, 'install', false);

		// delete files
		$state  = $this->deletePublicFiles($addon);
		$stateB = $this->deleteInternalFiles($addon);

		if ($stateB !== true) {
			// overwrite or concat stati
			$state = $state === true ? $stateB : $stateA.'<br />'.$stateB;
		}

		if ($state !== true) {
			return $state;
		}

		// notify listeners
		return $this->fireEvent('POST', 'UNINSTALL', $addon, false);
	}

	/**
	 * Activate an addOn
	 *
	 * @param  string $addon  addOn name
	 * @return mixed          true if successful, else an error message as a string
	 */
	public function activate($addon) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// check preconditions
		if ($aservice->isActivated($addon)) {
			return true;
		}

		if (!$aservice->isInstalled($addon)) {
			return t('addon_activate_failed', $addon);
		}

		// check requirements
		$msg = $this->checkRequirements($addon);
		if ($msg !== true) return $msg;

		// ask other addons about their plans
		$state = $this->fireEvent('PRE', 'ACTIVATE', $addon, true);
		if ($state !== true) return $state;

		$this->checkUpdate($addon);
		$aservice->setProperty($addon, 'status', true);
		$this->clearCache();

		return $this->fireEvent('POST', 'ACTIVATE', $addon, false);
	}

	/**
	 * Deactivate a addon
	 *
	 * @param  string $addon  addOn name
	 * @return mixed          true if successful, else an error message as a string
	 */
	public function deactivate($addon) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// check preconditions
		if (!$aservice->isActivated($addon)) {
			return true;
		}

		// check removal
		$state = $this->checkDependencies($addon);
		if ($state !== true) return $state;

		// ask other addons about their plans
		$state = $this->fireEvent('PRE', 'DEACTIVATE', $addon, true);
		if ($state !== true) return $state;

		$aservice->setProperty($addon, 'status', false);
		$this->clearCache();

		return $this->fireEvent('POST', 'DEACTIVATE', $addon, false);
	}

	/**
	 * Check if an addon may be removed
	 *
	 * @param  string $addon  addOn name
	 * @return mixed          true if successful, else an error message as a string
	 */
	private function checkDependencies($addon) {
		$service      = $this->addOnService;
		$dependencies = $service->getDependencies($addon, true, true);

		if (!empty($dependencies)) {
			return t('addon_requires_addon', $addon, reset($dependencies));
		}

		return true;
	}

	/**
	 * @param  string $addon  addOn name
	 * @return mixed          true if OK, else error message (string)
	 */
	private function checkRequirements($addon) {
		$aservice = $this->addOnService;
		$requires = $aservice->getRequirements($addon);

		foreach ($requires as $required) {
			if (!$aservice->isAvailable($required)) {
				return t('addon_requires_addon', $required, $addon);
			}
		}

		return true;
	}

	/**
	 * Check if a addOn version has changed
	 *
	 * This method detects changing versions and tries to include the
	 * update.php if available.
	 *
	 * @param string $addon  addOn name
	 */
	public function checkUpdate($addon) {
		$pservice = $this->pkgService;
		$version  = $pservice->getVersion($addon);
		$known    = $pservice->getKnownVersion($addon);

		if ($known !== null && $version !== null && $known !== $version) {
			$updateFile = $pservice->baseDirectory($addon).'update.php';

			if (file_exists($updateFile)) {
				$this->req($updateFile);
			}
		}

		if ($version !== null && $known !== $version) {
			$pservice->setKnownVersion($addon, $version);
		}
	}

	/**
	 * @param  string  $file
	 * @param  boolean $allowMyISAM
	 * @return mixed                 error message (string) or true
	 */
	private function installDump($file, $allowMyISAM) {
		try {
			$dump    = new sly_DB_Dump($file);
			$sql     = sly_DB_Persistence::getInstance();
			$queries = $dump->getQueries(true);

			// check queries for bad (i.e. non-InnoDB) engines

			if (!$allowMyISAM) {
				foreach ($queries as $idx => $query) {
					if (preg_match('#\bCREATE\s+TABLE\b#si', $query) && preg_match('#\bENGINE\s*=\s*([a-z]+)\b#si', $query, $match)) {
						$engine = strtolower($match[1]);

						if ($engine !== 'innodb') {
							return t('query_uses_forbidden_storage_engine', $idx+1, $match[1]);
						}
					}
				}

				// force InnoDB for CREATE statements without an ENGINE declaration
				$sql->exec('SET storage_engine = InnoDB');
			}

			foreach ($queries as $query) {
				$sql->query($query);
			}
		}
		catch (sly_DB_Exception $e) {
			return $e->getMessage();
		}

		return true;
	}

	public function clearCache() {
		sly_Core::cache()->flush('sly.addon', true);
	}

	public function loadAddOns() {
		// Make sure we don't accidentally load addons that have become
		// incompatible due to Sally and/or addon updates.
		if (sly_Core::isDeveloperMode()) {
			$this->refresh();
			$this->deactivateIncompatible();
		}

		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$cache    = sly_Core::cache();
		$order    = $cache->get('sly.addon', 'order');

		// if there is no cache yet, we load all addons the slow way
		if (!is_array($order)) {
			// reset our helper to keep track of the addon stati
			$this->loadInfo = array();

			foreach ($aservice->getRegisteredAddOns() as $pkg) {
				$this->load($pkg);
			}

			// and now we have a nice list that we can cache
			$cache->set('sly.addon', 'order', $this->loadInfo);
		}

		// yay, a cache, let's skip the whole dependency stuff
		else {
			foreach ($order as $addon => $info) {
				list($installed, $activated) = $info;

				// load addon config files
				$this->loadConfig($addon, $installed, $activated);

				// init the addon
				if ($activated) {
					$bootFile = $pservice->baseDirectory($addon).'boot.php';
					$this->req($bootFile);

					$this->loaded[$addon] = 1;
				}
			}
		}
	}

	/**
	 * @param string  $addon  addon name
	 * @param boolean $force  load the addon even if it's not active
	 */
	public function load($addon, $force = false) {
		if (isset($this->loaded[$addon])) {
			return true;
		}

		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		if (!$pservice->exists($addon)) {
			trigger_error('AddOn '.$addon.' does not exists. Assuming deletion and removing data.', E_USER_WARNING);
			$this->remove($addon);
			return false;
		}

		$compatible = $aservice->isCompatible($addon);
		$activated  = $compatible && $aservice->isAvailable($addon);
		$installed  = $compatible && ($activated || $aservice->isInstalled($addon));

		if ($installed || $force) {
			$this->loadConfig($addon, $installed, $activated);
			$this->loadInfo[$addon] = array($installed, $activated);
		}

		if ($activated || $force) {
			$requires = $pservice->getRequirements($addon, false);

			foreach ($requires as $required) {
				$this->load($required, $force);
			}

			$this->checkUpdate($addon);

			$bootFile = $pservice->baseDirectory($addon).'boot.php';
			$this->req($bootFile);

			$this->loaded[$addon] = 1;
		}
	}

	/**
	 * Loads the config files
	 *
	 * Loads the three config files an addon can provider: globals.yml,
	 * static.yml and defaults.yml.
	 *
	 * @param string  $addon      addon name
	 * @param boolean $installed
	 * @param boolean $activated
	 */
	protected function loadConfig($addon, $installed, $activated) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		if ($installed || $activated) {
			$config       = sly_Core::config();
			$baseFolder   = $pservice->baseDirectory($addon);
			$defaultsFile = $baseFolder.'defaults.yml';
			$globalsFile  = $baseFolder.'globals.yml';
			$staticFile   = $baseFolder.'static.yml';

			if ($activated) {
				if (file_exists($staticFile)) {
					$config->loadStatic($staticFile, $aservice->getConfPath($addon));
				}

				if (file_exists($defaultsFile)) {
					$config->loadProjectDefaults($defaultsFile, false, $aservice->getConfPath($addon));
				}
			}

			if (file_exists($globalsFile)) {
				$config->loadStatic($globalsFile);
			}
		}
	}

	/**
	 * @return array  list of found addOns
	 */
	public function refresh() {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$packages = $pservice->findPackages();

		// remove missing addOns
		$registered = $aservice->getRegisteredAddOns();

		foreach ($registered as $addon) {
			if (!$pservice->exists($addon)) {
				$this->remove($addon);
				$this->deletePublicFiles($addon);
				$this->deleteInternalFiles($addon);

				$this->clearCache();
			}
		}

		// add new addOns
		foreach ($packages as $pkg) {
			if (!$aservice->isRegistered($pkg)) {
				$this->add($pkg);
			}
		}

		natcasesort($packages);
		return $packages;
	}

	/**
	 * @param  string  $time
	 * @param  string  $type
	 * @param  string  $addon
	 * @param  boolean $filter
	 * @return mixed
	 */
	protected function fireEvent($time, $type, $addon, $filter) {
		$event  = 'SLY_ADDON_'.$time.'_'.$type;
		$params = compact('addon');

		if ($filter) {
			return sly_Core::dispatcher()->filter($event, true, $params);
		}

		sly_Core::dispatcher()->notify($event, true, $params);
		return true;
	}
}
