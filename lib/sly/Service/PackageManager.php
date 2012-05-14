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
class sly_Service_PackageManager {
	protected $pkgService; ///< sly_Service_Package
	protected $loadInfo;   ///< array
	protected $loaded;     ///< array

	/**
	 * @param sly_Service_Package $sourceDir
	 */
	public function __construct(sly_Service_Package $pkgService) {
		$this->pkgService = $pkgService;
		$this->loadInfo   = array();
		$this->loaded     = array();
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
	 * Check if a package has changed and must be disabled
	 *
	 * @return boolean  true if there were changes, else false
	 */
	public function deactivateIncompatible() {
		$pserv    = $this->pkgService;
		$packages = $pserv->getAvailablePackages();
		$changes  = false;

		foreach ($packages as $package) {
			$compatible = $pserv->isCompatible($package);
			if ($compatible && $pserv->exists($package)) continue;

			// disable all dependencies
			$deps = $pserv->getRecursiveDependencies($package);

			foreach ($deps as $dep) {
				$pserv->setProperty($dep, 'status', false);
			}

			// disable package itself
			$pserv->setProperty($package, 'status', false);

			// remember this
			$changes = true;
		}

		if ($changes) {
			$this->clearLoadCache();
		}

		return $changes;
	}

	/**
	 * Install a package
	 *
	 * @param  string  $package      package name
	 * @param  boolean $installDump
	 * @return mixed                 message or true if successful
	 */
	public function install($package, $installDump = true) {
		$pservice = $this->pkgService;
		$baseDir  = $pservice->baseDirectory($package);
		$bootFile = $baseDir.'boot.php';

		// return error message if a package wants to stop the install process
		$state = $pservice->fireEvent('PRE', 'INSTALL', $package, true);
		if ($state !== true) return $state;

		// check for boot.php before we do anything
		if (!is_readable($bootFile)) {
			return t('package_boot_file_not_found', $package);
		}

		// check requirements
		$msg = $this->checkRequirements($package);
		if ($msg !== true) return $msg;

		// check Sally version
		$sallyVersions = $pservice->getRequiredSallyVersions($package);

		if (!empty($sallyVersions)) {
			if (!$pservice->isCompatible($package)) {
				return t('package_incompatible', $package, sly_Core::getVersion('X.Y.Z'));
			}
		}
		else {
			return t('package_has_no_sally_version_info', $package);
		}

		// include install.php if available
		$installFile = $baseDir.'install.php';

		if (is_readable($installFile)) {
			try {
				$this->req($installFile);
			}
			catch (Exception $e) {
				return t('package_install_failed', $package, $e->getMessage());
			}
		}

		// read install.sql and install DB
		$installSQL = $baseDir.'install.sql';

		if ($installDump && is_readable($installSQL)) {
			$mysiam = $pservice->getComposerKey($package, 'allow_non_innodb', false);
			$state  = $this->installDump($installSQL, $mysiam);

			if ($state !== true) {
				return t('package_install_sql_failed', $package, $state);
			}
		}

		// copy assets to data/dyn/public
		$this->copyAssets($package);

		// load globals.yml
		$globalsFile = $baseDir.'globals.yml';

		if (file_exists($globalsFile) && !$pservice->isAvailable($package)) {
			sly_Core::config()->loadStatic($globalsFile);
		}

		// mark package as installed
		$pservice->setProperty($package, 'install', true);

		// store current package version
		$version = $pservice->getVersion($package);

		if ($version !== null) {
			sly_Util_Versions::set($pservice->getVersionKey($package), $version);
		}

		// notify listeners
		return $pservice->fireEvent('POST', 'INSTALL', $package, false);
	}

	/**
	 * Uninstall a package
	 *
	 * @param  string $package  package name
	 * @return mixed            message or true if successful
	 */
	public function uninstall($package) {
		$pservice = $this->pkgService;

		// if not installed, try to disable if needed
		if (!$pservice->isInstalled($package)) {
			return $this->deactivate($package);
		}

		// check for dependencies
		$state = $this->checkRemoval($package);
		if ($state !== true) return $state;

		// stop if (another) package forbids uninstall
		$state = $pservice->fireEvent('PRE', 'UNINSTALL', $package, true);
		if ($state !== true) return $state;

		// deactivate package first
		$state = $this->deactivate($package);
		if ($state !== true) return $state;

		// include uninstall.php if available
		$baseDir   = $this->baseDirectory($package);
		$uninstall = $baseDir.'uninstall.php';

		if (is_readable($uninstall)) {
			try {
				$this->req($uninstall);
			}
			catch (Exception $e) {
				return t('package_uninstall_failed', $package, $e->getMessage());
			}
		}

		// read uninstall.sql
		$uninstallSQL = $baseDir.'uninstall.sql';

		if (is_readable($uninstallSQL)) {
			$state = $this->installDump($uninstallSQL);

			if ($state !== true) {
				return t('package_uninstall_sql_failed', $package, $state);
			}
		}

		// mark package as not installed
		$pservice->setProperty($package, 'install', false);

		// delete files
		$state  = $pservice->deletePublicFiles($package);
		$stateB = $pservice->deleteInternalFiles($package);

		if ($stateB !== true) {
			// overwrite or concat stati
			$state = $state === true ? $stateB : $stateA.'<br />'.$stateB;
		}

		if ($state !== true) {
			return $state;
		}

		// notify listeners
		return $pservice->fireEvent('POST', 'UNINSTALL', $package, false);
	}

	/**
	 * Activate a package
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	public function activate($package) {
		$pservice = $this->pkgService;

		// check preconditions
		if ($pservice->isActivated($package)) {
			return true;
		}

		if (!$pservice->isInstalled($package)) {
			return t('package_activate_failed', $package);
		}

		// check requirements
		$msg = $this->checkRequirements($package);
		if ($msg !== true) return $msg;

		// ask other packages about their plans
		$state = $pservice->fireEvent('PRE', 'ACTIVATE', $package, true);
		if ($state !== true) return $state;

		$this->checkUpdate($package);
		$pservice->setProperty($package, 'status', true);
		$this->clearLoadCache();

		return $pservice->fireEvent('POST', 'ACTIVATE', $package, false);
	}

	/**
	 * Deactivate a package
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	public function deactivate($package) {
		$pservice = $this->pkgService;

		// check preconditions
		if (!$pservice->isActivated($package)) {
			return true;
		}

		// check removal
		$state = $this->checkRemoval($package);
		if ($state !== true) return $state;

		// ask other packages about their plans
		$state = $pservice->fireEvent('PRE', 'DEACTIVATE', $package, true);
		if ($state !== true) return $state;

		$pservice->setProperty($package, 'status', false);
		$this->clearLoadCache();

		return $pservice->fireEvent('POST', 'DEACTIVATE', $package, false);
	}

	/**
	 * Check if a package may be removed
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	private function checkRemoval($package) {
		$pservice     = $this->pkgService;
		$dependencies = $pservice->getDependencies($package, true);

		if (!empty($dependencies)) {
			return t('package_requires_package', $package, reset($dependencies));
		}

		return true;
	}

	/**
	 * Check if a package version has changed
	 *
	 * This method detects changing versions and tries to include the
	 * update.php if available.
	 *
	 * @param string $package  package name
	 */
	public function checkUpdate($package) {
		$pservice = $this->pkgService;
		$version  = $pservice->getVersion($package);
		$known    = $pservice->getKnownVersion($package);

		if ($known !== null && $version !== null && $known !== $version) {
			$updateFile = $pservice->baseDirectory($package).'update.php';

			if (file_exists($updateFile)) {
				$this->req($updateFile);
			}
		}

		if ($version !== null && $known !== $version) {
			sly_Util_Versions::set($pservice->getVersionKey($package), $version);
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

	/**
	 * @param  string $package  package name
	 * @return mixed            true if OK, else error message (string)
	 */
	private function checkRequirements($package) {
		$pservice = $this->pkgService;
		$requires = $pservice->getRequirements($package);

		foreach ($requires as $required) {
			if (!$pservice->isAvailable($required)) {
				return t('package_requires_package', $required, $package);
			}
		}

		return true;
	}

	public function clearLoadCache() {
		sly_Core::cache()->flush('sly.pkgmanager');
	}

	public function loadPackages() {
		// Make sure we don't accidentally load packages that have become
		// incompatible due to Sally and/or package updates.
		if (sly_Core::isDeveloperMode()) {
			$this->refreshPackages();
			$this->deactivateIncompatible();
		}

		$pservice = $this->pkgService;
		$cache    = sly_Core::cache();
		$order    = $cache->get('sly.pkgmanager', 'order');

		// if there is no cache yet, we load all packages the slow way
		if (!is_array($order)) {
			// reset our helper to keep track of the package stati
			$this->loadInfo = array();

			foreach ($pservice->getRegisteredPackages() as $pkg) {
				$this->load($pkg);
			}

			// and now we have a nice list that we can cache
			$cache->set('sly.pkgmanager', 'order', $this->loadInfo);
		}

		// yay, a cache, let's skip the whole dependency stuff
		else {
			foreach ($order as $package => $info) {
				list($installed, $activated) = $info;

				// load package config files
				$this->loadConfig($package, $installed, $activated);

				// init the package
				if ($activated) {
					$bootFile = $pservice->baseDirectory($package).'boot.php';
					$this->req($bootFile);

					$this->loaded[$package] = 1;
				}
			}
		}
	}

	/**
	 * @param string  $package  package name
	 * @param boolean $force    load the package even if it's not active
	 */
	public function load($package, $force = false) {
		if (isset($this->loaded[$package])) {
			return true;
		}

		$pservice = $this->pkgService;

		if (!$pservice->exists($package)) {
			trigger_error('Package '.$package.' does not exists. Assuming deletion and removing data.', E_USER_WARNING);
			$pservice->remove($package);
			return false;
		}

		$compatible = $pservice->isCompatible($package);
		$activated  = $compatible && $pservice->isAvailable($package);
		$installed  = $compatible && ($activated || $pservice->isInstalled($package));

		if ($installed || $force) {
			$this->loadConfig($package, $installed, $activated);
			$this->loadInfo[$package] = array($installed, $activated);
		}

		if ($activated || $force) {
			$requires = $pservice->getRequirements($package);

			foreach ($requires as $required) {
				$this->load($required, $force);
			}

			$this->checkUpdate($package);

			$bootFile = $pservice->baseDirectory($package).'boot.php';
			$this->req($bootFile);

			$this->loaded[$package] = 1;
		}
	}

	/**
	 * Loads the config files
	 *
	 * Loads the three config files a package can provider: globals.yml,
	 * static.yml and defaults.yml.
	 *
	 * @param string  $package    package name
	 * @param boolean $installed
	 * @param boolean $activated
	 */
	protected function loadConfig($package, $installed, $activated) {
		$pservice = $this->pkgService;

		if ($installed || $activated) {
			$config       = sly_Core::config();
			$baseFolder   = $pservice->baseDirectory($package);
			$defaultsFile = $baseFolder.'defaults.yml';
			$globalsFile  = $baseFolder.'globals.yml';
			$staticFile   = $baseFolder.'static.yml';

			if ($activated) {
				if (file_exists($staticFile)) {
					$config->loadStatic($staticFile, $pservice->getConfPath($package));
				}

				if (file_exists($defaultsFile)) {
					$config->loadProjectDefaults($defaultsFile, false, $pservice->getConfPath($package));
				}
			}

			if (file_exists($globalsFile)) {
				$config->loadStatic($globalsFile);
			}
		}
	}

	/**
	 * @return array  list of found packages
	 */
	public function refreshPackages() {
		$pservice = $this->pkgService;
		$root     = $pservice->baseDirectory();
		$packages = array();
		$dirs     = $this->readDir($root);

		foreach ($dirs as $dir) {
			// evil package not conforming to naming convention
			if ($pservice->exists($dir)) {
				$packages[] = $dir;
			}
			else {
				$subdirs = $this->readDir($root.'/'.$dir);

				foreach ($subdirs as $subdir) {
					// good package
					if ($pservice->exists($dir.'/'.$subdir)) {
						$packages[] = $dir.'/'.$subdir;
					}
				}
			}
		}

		// remove missing components
		$registered = $pservice->getRegisteredPackages();

		foreach ($registered as $pkg) {
			if (!$pservice->exists($pkg)) {
				$pservice->remove($pkg);
				$pservice->deletePublicFiles($pkg);
				$pservice->deleteInternalFiles($pkg);

				$this->clearLoadCache();
			}
		}

		// add new packages
		foreach ($packages as $pkg) {
			if (!$pservice->isRegistered($pkg)) {
				$pservice->add($pkg);
			}
		}

		natcasesort($packages);
		return $packages;
	}

	private function readDir($dir) {
		$dir = new sly_Util_Directory($dir);
		return $dir->exists() ? $dir->listPlain(false, true) : array();
	}
}
