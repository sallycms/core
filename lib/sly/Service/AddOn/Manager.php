<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use wv\BabelCache\CacheInterface;

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_AddOn_Manager {
	protected $config;       ///< sly_Configuration
	protected $dispatcher;   ///< sly_Event_IDispatcher
	protected $cache;        ///< CacheInterface
	protected $addOnService; ///< sly_Service_AddOn
	protected $pkgService;   ///< sly_Service_Package
	protected $loadInfo;     ///< array
	protected $loaded;       ///< array

	/**
	 * @param sly_Configuration     $config
	 * @param sly_Event_IDispatcher $dispatcher
	 * @param CacheInterface        $cache
	 * @param sly_Service_AddOn     $service
	 */
	public function __construct(sly_Configuration $config, sly_Event_IDispatcher $dispatcher,
		CacheInterface $cache, sly_Service_AddOn $service) {
		$this->config       = $config;
		$this->dispatcher   = $dispatcher;
		$this->cache        = $cache;
		$this->addOnService = $service;
		$this->pkgService   = $service->getPackageService();
		$this->loadInfo     = array();
		$this->loaded       = array();
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
		$pservice = $this->pkgService;
		$path     = $this->addOnService->getConfPath($addon);

		$this->config->remove($path)->store();
		sly_Util_Versions::remove($pservice->getVersionKey($addon));

		$this->clearCache();
	}

	/**
	 * Removes all dyn files
	 *
	 * @param string $addon  addon name
	 */
	public function deleteDynFiles($addon) {
		$fs = $this->addOnService->getDynFilesystem($addon);

		$this->fireEvent('PRE', 'DELETE_DYN_FILES', $addon, array('filesystem' => $fs));

		try {
			$service = new sly_Filesystem_Service($fs);
			$service->deleteAllFiles();
		}
		catch (Exception $e) {
			throw new sly_Exception(t('addon_cleanup_failed', $e->getMessage()));
		}

		$this->fireEvent('POST', 'DELETE_DYN_FILES', $addon, array('filesystem' => $fs));
	}

	/**
	 * Removes all temporary files
	 *
	 * @param string $addon  addon name
	 */
	public function deleteTempFiles($addon) {
		$dir = $this->addOnService->getTempDirectory($addon);
		$fs  = new Filesystem(new Local($dir));

		$this->fireEvent('PRE', 'DELETE_TEMP_FILES', $addon, array('filesystem' => $fs));

		try {
			$service = new sly_Filesystem_Service($fs);
			$service->deleteAllFiles();
		}
		catch (Exception $e) {
			throw new sly_Exception(t('addon_cleanup_failed'));
		}

		$this->fireEvent('POST', 'DELETE_TEMP_FILES', $addon, array('filesystem' => $fs));
	}

	/**
	 * Include file
	 *
	 * This prevents the included file from messing with the variables of the
	 * surrounding code.
	 *
	 * @param string        $filename
	 * @param sly_Container $container  the DI container to use for the script
	 */
	protected function req($filename, sly_Container $container = null) {
		if (!$container) unset($container);
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
	 * @throws sly_Exception                    in case anything goes wrong
	 * @param  string             $addon        addOn name
	 * @param  boolean            $installDump
	 * @param  sly_DB_Persistence $persistence  database to use when installing the dump (may only be null if $installDump is false)
	 * @param  sly_Container      $container    DI container for the install.php
	 */
	public function install($addon, $installDump = true, sly_DB_Persistence $persistence = null, sly_Container $container = null) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$baseDir  = $pservice->baseDirectory($addon);
		$bootFile = $baseDir.'boot.php';

		if ($installDump && !$persistence) {
			throw new LogicException('You must give a persistence instance when installing a dump.');
		}

		// return error message if an addon wants to stop the install process
		$this->fireEvent('PRE', 'INSTALL', $addon);

		// check for boot.php before we do anything
		if (!is_readable($bootFile)) {
			throw new sly_Exception(t('addon_boot_file_not_found', $addon));
		}

		// check requirements
		$this->checkRequirements($addon);

		// check Sally version
		$sallyVersion = $aservice->getRequiredSallyVersion($addon);

		if (!empty($sallyVersion)) {
			if (!$aservice->isCompatible($addon)) {
				throw new sly_Exception(t('addon_incompatible', $addon, sly_Core::getVersion('R')));
			}
		}
		else {
			throw new sly_Exception(t('addon_has_no_sally_version_info', $addon));
		}

		// include install.php if available
		$installFile = $baseDir.'install.php';

		if (is_readable($installFile)) {
			try {
				$this->req($installFile, $container);
			}
			catch (Exception $e) {
				throw new sly_Exception(t('addon_install_failed', $addon, $e->getMessage()));
			}
		}

		// read install.sql and install DB
		$installSQL = $baseDir.'install.sql';

		if ($installDump && is_readable($installSQL)) {
			$mysiam = $pservice->getKey($addon, 'allow_non_innodb', false);
			$this->installDump($persistence, $installSQL, $mysiam, 'install');
		}

		// load globals.yml
		$this->loadConfig($addon, false, true);

		// mark addOn as installed
		$aservice->setProperty($addon, 'install', true);

		// store current addOn version
		$version = $pservice->getVersion($addon);

		if ($version !== null) {
			$pservice->setKnownVersion($addon, $version);
		}

		$defaultsFile = $baseDir.'defaults.yml';
		sly_Util_Configuration::loadYamlFile($this->config, $defaultsFile, false);

		// notify listeners
		$this->fireEvent('POST', 'INSTALL', $addon);
	}

	/**
	 * Uninstall an addOn
	 *
	 * @throws sly_Exception                    in case anything goes wrong
	 * @param  string             $addon        addOn name
	 * @param  sly_DB_Persistence $persistence  database to use when executing the uninstall.sql
	 * @param  sly_Container      $container    DI container for the uninstall.php
	 */
	public function uninstall($addon, sly_DB_Persistence $persistence, sly_Container $container = null) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// if not installed, try to disable if needed
		if (!$aservice->isInstalled($addon)) {
			$this->deactivate($addon);
			return;
		}

		// check for dependencies
		$this->checkDependencies($addon);

		// stop if (another) addon forbids uninstall
		$this->fireEvent('PRE', 'UNINSTALL', $addon);

		// deactivate addon first
		$this->deactivate($addon);

		// include uninstall.php if available
		$baseDir   = $pservice->baseDirectory($addon);
		$uninstall = $baseDir.'uninstall.php';

		if (is_readable($uninstall)) {
			try {
				$this->req($uninstall, $container);
			}
			catch (Exception $e) {
				throw new sly_Exception(t('addon_uninstall_failed', $addon, $e->getMessage()));
			}
		}

		// read uninstall.sql
		$uninstallSQL = $baseDir.'uninstall.sql';

		if (is_readable($uninstallSQL)) {
			$this->installDump($persistence, $uninstallSQL, false, 'uninstall');
		}

		// mark addOn as not installed
		$aservice->setProperty($addon, 'install', false);

		// delete files
		$this->deleteDynFiles($addon);
		$this->deleteTempFiles($addon);

		// remove version data
		sly_Util_Versions::remove($pservice->getVersion($addon));

		// remove configuration defaults (and a poorly little bit more)
		$this->remove($addon);

		// restore empty default config (if you want the config to be deleted, delete the addon)
		$this->add($addon);

		// notify listeners
		$this->fireEvent('POST', 'UNINSTALL', $addon);
	}

	/**
	 * Activate an addOn
	 *
	 * @throws sly_Exception             in case anything goes wrong
	 * @param  string        $addon      addOn name
	 * @param  sly_Container $container  DI container for the update.php (if needed)
	 */
	public function activate($addon, sly_Container $container) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// check preconditions
		if ($aservice->isActivated($addon)) {
			return;
		}

		if (!$aservice->isInstalled($addon)) {
			throw new sly_Exception(t('addon_activate_failed', $addon));
		}

		// check requirements
		$this->checkRequirements($addon);

		// ask other addons about their plans
		$this->fireEvent('PRE', 'ACTIVATE', $addon);

		$this->checkUpdate($addon, $container);
		$aservice->setProperty($addon, 'status', true);
		$this->clearCache();

		$this->fireEvent('POST', 'ACTIVATE', $addon);
	}

	/**
	 * Deactivate a addon
	 *
	 * @throws sly_Exception  in case anything goes wrong
	 * @param  string $addon  addOn name
	 */
	public function deactivate($addon) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;

		// check preconditions
		if (!$aservice->isActivated($addon)) {
			return;
		}

		// check removal
		$this->checkDependencies($addon);

		// ask other addons about their plans
		$this->fireEvent('PRE', 'DEACTIVATE', $addon);

		$aservice->setProperty($addon, 'status', false);
		$this->clearCache();

		$this->fireEvent('POST', 'DEACTIVATE', $addon);
	}

	/**
	 * Check if an addon may be removed
	 *
	 * @throws sly_Exception  in case anything goes wrong
	 * @param  string $addon  addOn name
	 */
	private function checkDependencies($addon) {
		$service      = $this->addOnService;
		$dependencies = $service->getDependencies($addon, true, true);

		if (!empty($dependencies)) {
			throw new sly_Exception(t('addon_requires_addon', $addon, reset($dependencies)));
		}
	}

	/**
	 * @throws sly_Exception  in case anything goes wrong
	 * @param  string $addon  addOn name
	 */
	private function checkRequirements($addon) {
		$aservice = $this->addOnService;
		$requires = $aservice->getRequirements($addon);

		foreach ($requires as $required) {
			if (!$aservice->isAvailable($required)) {
				throw new sly_Exception(t('addon_requires_addon', $required, $addon));
			}
		}
	}

	/**
	 * Check if a addOn version has changed
	 *
	 * This method detects changing versions and tries to include the
	 * update.php if available.
	 *
	 * @param string        $addon      addOn name
	 * @param sly_Container $container  DI container for the update.php
	 */
	public function checkUpdate($addon, sly_Container $container = null) {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$version  = $pservice->getVersion($addon);
		$known    = $pservice->getKnownVersion($addon);
		$baseDir  = $pservice->baseDirectory($addon);

		if ($known !== null && $version !== null && $known !== $version) {
			$updateFile   = $baseDir.'update.php';
			$defaultsFile = $baseDir.'defaults.yml';

			sly_Util_Configuration::loadYamlFile($this->config, $defaultsFile, false);

			if (file_exists($updateFile)) {
				$this->req($updateFile, $container);
			}
		}

		if ($version !== null && $known !== $version) {
			$pservice->setKnownVersion($addon, $version);
		}
	}

	/**
	 * @throws sly_Exception                    in case anything goes wrong
	 * @param  sly_DB_Persistence $persistence
	 * @param  string             $file
	 * @param  boolean            $allowMyISAM
	 * @param  string             $type         'install' or 'uninstall'
	 */
	private function installDump(sly_DB_Persistence $persistence, $file, $allowMyISAM, $type) {
		try {
			$dump    = new sly_DB_Dump($file);
			$queries = $dump->getQueries(true);

			// check queries for bad (i.e. non-InnoDB) engines

			if (!$allowMyISAM) {
				foreach ($queries as $idx => $query) {
					if (preg_match('#\bCREATE\s+TABLE\b#si', $query) && preg_match('#\bENGINE\s*=\s*([a-z]+)\b#si', $query, $match)) {
						$engine = strtolower($match[1]);

						if ($engine !== 'innodb') {
							throw new sly_Exception(t('query_uses_forbidden_storage_engine', $idx+1, $match[1]));
						}
					}
				}

				// force InnoDB for CREATE statements without an ENGINE declaration
				$persistence->exec('SET storage_engine = InnoDB');
			}

			foreach ($queries as $query) {
				$persistence->query($query);
			}
		}
		catch (sly_DB_Exception $e) {
			throw new sly_Exception(t('addon_'.$type.'_sql_failed', $addon, $e->getMessage()));
		}
	}

	public function clearCache() {
		$this->addOnService->clearCache();
	}

	/**
	 * load all enabled addOns
	 *
	 * @param sly_Container $container  DI container for the boot.php files
	 */
	public function loadAddOns(sly_Container $container = null) {
		// Make sure we don't accidentally load addons that have become
		// incompatible due to Sally and/or addon updates.
		if (sly_Core::isDeveloperMode()) {
			$this->refresh();
			$this->deactivateIncompatible();
		}

		$aservice       = $this->addOnService;
		$pservice       = $this->pkgService;
		$this->loadInfo = $this->cache->get('sly.addon', 'order');

		// if there is no cache yet, we load all addons the slow way
		if (!is_array($this->loadInfo)) {
			// reset our helper to keep track of the addon stati
			$this->loadInfo = array();

			$this->collectLoadingInfo($aservice->getRegisteredAddOns());

			// and now we have a nice list that we can cache
			$this->cache->set('sly.addon', 'order', $this->loadInfo);
		}

		// first load all configs
		foreach ($this->loadInfo as $addon => $info) {
			list($installed, $activated) = $info;

			// load addon config files
			$this->loadConfig($addon, $installed, $activated);
		}

		// then boot the addons
		foreach ($this->loadInfo as $addon => $info) {
			list($installed, $activated) = $info;

			// init the addon
			if ($activated) {
				$bootFile = $pservice->baseDirectory($addon).'boot.php';
				$this->req($bootFile, $container);

				$this->loaded[$addon] = 1;
			}
		}
	}

	protected function collectLoadingInfo($addOnsToLoad) {
		$aservice = $this->addOnService;

		foreach($addOnsToLoad as $addon) {
			$compatible = $aservice->isCompatible($addon);
			$activated  = $compatible && $aservice->isAvailable($addon);
			$installed  = $compatible && ($activated || $aservice->isInstalled($addon));

			if ($activated) {
				$requires = $aservice->getRequirements($addon, false);
				$this->collectLoadingInfo($requires);
			}

			$this->loadInfo[$addon] = array($installed, $activated);
		}
	}

	/**
	 * @param string        $addon      addon name
	 * @param boolean       $force      load the addon even if it's not active
	 * @param sly_Container $container  DI container for the boot.php
	 */
	public function load($addon, $force = false, sly_Container $container = null) {
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
		}

		if ($activated || $force) {
			$requires = $aservice->getRequirements($addon, false);

			foreach ($requires as $required) {
				$this->load($required, $force, $container);
			}

			$this->checkUpdate($addon, $container);

			$bootFile = $pservice->baseDirectory($addon).'boot.php';
			$this->req($bootFile, $container);

			$this->loaded[$addon] = 1;
		}

		if ($installed || $activated || $force) {
			$this->loadInfo[$addon] = array($installed, $activated);
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
		$pservice = $this->pkgService;

		if ($installed || $activated) {
			$baseFolder   = $pservice->baseDirectory($addon);
			$globalsFile  = $baseFolder.'globals.yml';
			$staticFile   = $baseFolder.'static.yml';

			if ($activated) {
				sly_Util_Configuration::loadYamlFile($this->config, $staticFile, true);
			}

			if (file_exists($globalsFile)) {
				sly_Util_Configuration::loadYamlFile($this->config, $globalsFile, true);
			}
		}
	}

	/**
	 * @return array  list of found addOns
	 */
	public function refresh() {
		$aservice = $this->addOnService;
		$pservice = $this->pkgService;
		$packages = $pservice->getPackages();

		// remove missing addOns
		$registered = $aservice->getRegisteredAddOns();

		foreach ($registered as $addon) {
			if (!in_array($addon, $packages)) {
				$this->remove($addon);
				$this->deleteDynFiles($addon);
				$this->deleteTempFiles($addon);

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
	 * Fire a notify event regarding addOn state changes
	 *
	 * @param string $time    'PRE' or 'POST'
	 * @param string $type    'INSTALL', 'UNINSTALL', ...
	 * @param string $addon   the addOn that we operate on
	 * @param array  $params  additional event parameters
	 */
	protected function fireEvent($time, $type, $addon, array $params = array()) {
		$this->dispatcher->notify('SLY_ADDON_'.$time.'_'.$type, $addon, $params);
	}
}
