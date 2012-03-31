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
abstract class sly_Service_AddOn_Base {
	protected static $loaded = array(); ///< array  list of loaded addOns and plugins for depedency aware loading
	private static $loadInfo = array();

	/**
	 * @param  mixed $component
	 * @return string
	 */
	abstract public function baseFolder($component);

	/**
	 * @param  mixed  $component
	 * @param  string $property
	 * @param  mixed  $value
	 * @return mixed
	 */
	abstract public function setProperty($component, $property, $value);

	/**
	 * @param  mixed  $component
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	abstract public function getProperty($component, $property, $default = null);

	/**
	 * @param  string $type
	 * @param  mixed  $component
	 * @return string
	 */
	abstract protected function dynFolder($type, $component);

	/**
	 * @param  string  $time
	 * @param  string  $type
	 * @param  mixed   $component
	 * @param  boolean $state
	 * @return mixed
	 */
	abstract protected function extend($time, $type, $component, $state);

	/**
	 * @param  mixed $component
	 * @return string
	 */
	abstract protected function getVersionKey($component);

	/**
	 * @param  mixed $component
	 * @return string
	 */
	abstract protected function getConfPath($component);

	/**
	 * @param  mixed $component
	 * @return boolean
	 */
	abstract protected function exists($component);

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
	 * Loads the YAML config file
	 *
	 * Loads globals.yml and defaults.yml.
	 *
	 * @param mixed $component  addOn as string, plugin as array
	 */
	protected function loadConfig($component, $forceInstall = false, $forceActivated = false) {
		if ($forceInstall || $forceActivated || $this->isInstalled($component)) {
			$config       = sly_Core::config();
			$baseFolder   = $this->baseFolder($component);
			$defaultsFile = $baseFolder.'defaults.yml';
			$globalsFile  = $baseFolder.'globals.yml';

			if ($forceActivated || $this->isActivated($component)) {
				$this->loadStatic($component, $baseFolder);

				if (file_exists($defaultsFile)) {
					$config->loadProjectDefaults($defaultsFile, false, $this->getConfPath($component));
				}
			}

			if (file_exists($globalsFile)) {
				$config->loadStatic($globalsFile);
			}
		}
	}

	/**
	 * @param mixed $component
	 */
	protected function loadStatic($component, $baseFolder = null) {
		$config     = sly_Core::config();
		$baseFolder = $baseFolder === null ? $this->baseFolder($component) : $baseFolder;
		$staticFile = $baseFolder.'static.yml';

		if (file_exists($staticFile)) {
			$config->loadStatic($staticFile, $this->getConfPath($component));
		}
	}

	/**
	 * Check if a version number matches
	 *
	 * This will take a well-formed version number (X.Y.Z) and compare it against
	 * the system version. You can leave out parts from the right to make it
	 * more general (i.e. '0.2' will match any 0.2.x version).
	 *
	 * @param  string $version  the version number to check against
	 * @return boolean          true for a match, else false
	 */
	public function checkVersion($version) {
		$thisVersion = sly_Core::getVersion('X.Y.Z');
		return preg_match('#^'.preg_quote($version, '#').'.*#i', $thisVersion) == 1;
	}

	/**
	 * Check if a component changed and must be disabled
	 *
	 * @return boolean  true if there were changes, else false
	 */
	public function deactivateIncompatibleComponents() {
		$addonService  = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();
		$changes       = false;

		foreach ($addonService->getInstalledAddons() as $addonName) {
			$oldVal   = $addonService->getProperty($addonName, 'compatible');
			$newVal   = $addonService->isCompatible($addonName, true);
			$changes |= ($oldVal !== $newVal);

			$addonService->setProperty($addonName, 'compatible', $newVal);

			// disable all dependencies
			if ($oldVal !== $newVal && $newVal === false) {
				$deps = $addonService->getRecursiveDependencies($addonName);
				$addonService->setProperty($addonName, 'status', false);

				foreach ($deps as $dep) {
					if (is_array($dep)) {
						$pluginService->setProperty($dep, 'status', false);
					}
					else {
						$addonService->setProperty($dep, 'status', false);
					}
				}
			}

			foreach ($pluginService->getInstalledPlugins($addonName) as $pluginName) {
				$plugin   = array($addonName, $pluginName);
				$oldVal   = $pluginService->getProperty($plugin, 'compatible');
				$newVal   = $pluginService->isCompatible($plugin, true);
				$changes |= ($oldVal !== $newVal);

				$pluginService->setProperty($plugin, 'compatible', $newVal);

				// disable all dependencies
				if ($oldVal !== $newVal && $newVal === false) {
					$deps = $pluginService->getRecursiveDependencies($plugin);
					$pluginService->setProperty($plugin, 'status', false);

					foreach ($deps as $dep) {
						if (is_array($dep)) {
							$pluginService->setProperty($dep, 'status', false);
						}
						else {
							$addonService->setProperty($dep, 'status', false);
						}
					}
				}
			}
		}

		if ($changes) {
			$this->clearLoadCache();
		}

		return $changes;
	}

	/**
	 * Adds a new component to the global config
	 *
	 * @param mixed $component  addOn as string, plugin as array
	 */
	public function add($component) {
		$this->setProperty($component, 'install', false);
		$this->setProperty($component, 'status', false);
		$this->setProperty($component, 'compatible', $this->isCompatible($component, true));

		// only add plugins key on addOns
		if (!is_array($component)) {
			$this->setProperty($component, 'plugins', array());
		}
	}

	/**
	 * Removes a component from the global config
	 *
	 * @param mixed $component  addOn as string, plugin as array
	 */
	public function removeConfig($component) {
		$config = sly_Core::config();
		$config->remove($this->getConfPath($component));
	}

	/**
	 * Get string with links to support pages
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return string            a comma separated list of URLs
	 */
	public function getSupportPageEx($component) {
		$supportPage = $this->getSupportPage($component, '');
		$author      = $this->getAuthor($component);

		if ($supportPage) {
			$supportPages = sly_makeArray($supportPage);
			$links        = array();

			foreach ($supportPages as $idx => $page) {
				$infos = parse_url($page);
				if (!isset($infos['host'])) $infos = parse_url('http://'.$page);
				if (!isset($infos['host'])) continue;

				$page = sprintf('%s://%s%s', $infos['scheme'], $infos['host'], isset($infos['path']) ? $infos['path'] : '');
				$host = substr($infos['host'], 0, 4) == 'www.' ? substr($infos['host'], 4) : $infos['host'];
				$name = $idx === 0 && !empty($author) ? $author : $host;
				$name = sly_Util_String::cutText($name, 40);

				$links[] = '<a href="'.sly_html($page).'" class="sly-blank">'.sly_html($name).'</a>';
			}

			$supportPage = implode(', ', $links);
		}

		return $supportPage;
	}

	/**
	 * @param  mixed   $component    addOn as string, plugin as array
	 * @return string
	 */
	private function buildComponentName($component) {
		return is_array($component) ? implode('/', $component) : $component;
	}

	/**
	 * Install a component
	 *
	 * @param  mixed   $component    addOn as string, plugin as array
	 * @param  boolean $installDump
	 * @return mixed                 message or true if successful
	 */
	public function install($component, $installDump = true) {
		$baseDir    = $this->baseFolder($component);
		$configFile = $baseDir.'config.inc.php';
		$name       = $this->buildComponentName($component);

		// return error message if an addOn wants to stop the install process

		$state = $this->extend('PRE', 'INSTALL', $component, true);

		if ($state !== true) {
			return $state;
		}

		// check for config.inc.php before we do anything

		if (!is_readable($configFile)) {
			return t('component_config_not_found');
		}

		// check requirements

		if (!$this->isAvailable($component)) {
			$this->loadStatic($component); // static.yml
		}

		$msg = $this->checkRequirements($component);

		if ($msg !== true) {
			return $msg;
		}

		// check Sally version

		$sallyVersions = $this->getProperty($component, 'sally');

		if (!empty($sallyVersions)) {
			if (!$this->isCompatible($component)) {
				return t('component_incompatible', $name, sly_Core::getVersion('X.Y.Z'));
			}
		}
		else {
			return t('component_has_no_sally_version_info', $name);
		}

		// include install.inc.php if available

		$installFile = $baseDir.'install.inc.php';

		if (is_readable($installFile)) {
			try {
				$this->req($installFile);
			}
			catch (Exception $e) {
				return t('component_install_failed', $name, $e->getMessage());
			}
		}

		// read install.sql and install DB

		$installSQL = $baseDir.'install.sql';

		if ($installDump && is_readable($installSQL)) {
			$state = $this->installDump($installSQL);

			if ($state !== true) {
				return t('component_install_sql_failed', $name, $state);
			}
		}

		// copy assets to data/dyn/public

		if (is_dir($baseDir.'assets')) {
			$this->copyAssets($component);
		}

		// load globals.yml

		$globalsFile = $this->baseFolder($component).'globals.yml';

		if (!$this->isAvailable($component) && file_exists($globalsFile)) {
			sly_Core::config()->loadStatic($globalsFile);
		}

		// mark component as installed
		$this->setProperty($component, 'install', true);
		$this->clearLoadCache();

		// store current component version
		$version = $this->getProperty($component, 'version', false);

		if ($version !== false) {
			sly_Util_Versions::set($this->getVersionKey($component), $version);
		}

		// notify listeners
		return $this->extend('POST', 'INSTALL', $component, true);
	}

	/**
	 * Uninstall a component
	 *
	 * @param  $component  addOn as string, plugin as array
	 * @return mixed       message or true if successful
	 */
	public function uninstall($component) {
		// if not installed, try to disable if needed

		if (!$this->isInstalled($component)) {
			return $this->deactivate($component);
		}

		// check for dependencies

		$state = $this->checkRemoval($component);
		if ($state !== true) return $state;

		// stop if addOn forbids uninstall

		$state = $this->extend('PRE', 'UNINSTALL', $component, true);

		if ($state !== true) {
			return $state;
		}

		// deactivate addOn first

		$state = $this->deactivate($component);

		if ($state !== true) {
			return $state;
		}

		// include uninstall.inc.php if available

		$baseDir       = $this->baseFolder($component);
		$uninstallFile = $baseDir.'uninstall.inc.php';
		$name          = $this->buildComponentName($component);

		if (is_readable($uninstallFile)) {
			try {
				$this->req($uninstallFile);
			}
			catch (Exception $e) {
				return t('component_uninstall_failed', $name, $e->getMessage());
			}
		}

		// read uninstall.sql

		$uninstallSQL = $baseDir.'uninstall.sql';

		if (is_readable($uninstallSQL)) {
			$state = $this->installDump($uninstallSQL);

			if ($state !== true) {
				return t('component_uninstall_sql_failed', $name, $state);
			}
		}

		// mark component as not installed
		$this->setProperty($component, 'install', false);
		$this->clearLoadCache();

		// delete files
		$state  = $this->deletePublicFiles($component);
		$stateB = $this->deleteInternalFiles($component);

		if ($stateB !== true) {
			// overwrite or concat stati
			$state = $state === true ? $stateB : $stateA.'<br />'.$stateB;
		}

		// notify listeners
		return $this->extend('POST', 'UNINSTALL', $component, $state);
	}

	/**
	 * Activate a component
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	public function activate($component) {
		if ($this->isActivated($component)) {
			return true;
		}

		$name = $this->buildComponentName($component);

		if (!$this->isInstalled($component, $name)) {
			return t('component_activate_failed');
		}

		// We can't use the service to get the list of required addOns since the
		// static.yml has not yet been loaded.

		@$this->loadStatic($component);

		$msg = $this->checkRequirements($component);

		if ($msg !== true) {
			return $msg;
		}

		$state = $this->extend('PRE', 'ACTIVATE', $component, true);

		if ($state !== true) {
			return $state;
		}

		$this->checkUpdate($component);
		$this->setProperty($component, 'status', true);
		$this->clearLoadCache();

		return $this->extend('POST', 'ACTIVATE', $component, true);
	}

	/**
	 * Deactivate a component
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	public function deactivate($component) {
		if (!$this->isActivated($component)) {
			return true;
		}

		$state = $this->checkRemoval($component);
		if ($state !== true) return $state;

		$state = $this->extend('PRE', 'DEACTIVATE', $component, true);
		if ($state !== true) return $state;

		$this->setProperty($component, 'status', false);
		$this->clearLoadCache();

		return $this->extend('POST', 'DEACTIVATE', $component, true);
	}

	/**
	 * Check if a component may be removed
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	private function checkRemoval($component) {
		// Check if this component is required

		$dependencies = $this->getDependencies($component, true);

		if (!empty($dependencies)) {
			$dep  = reset($dependencies);
			$msg  = is_array($dep) ? 'requires_plugin' : 'requires_addon';
			$comp = $this->buildComponentName($component);
			$dep  = $this->buildComponentName($dep);

			return t('component_'.$msg, $comp, $dep);
		}

		return true;
	}

	/**
	 * Get the full path to the public folder
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return string            full path
	 */
	public function publicFolder($component) {
		return $this->dynFolder('public', $component);
	}

	/**
	 * Get the full path to the internal folder
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return string            full path
	 */
	public function internalFolder($component) {
		return $this->dynFolder('internal', $component);
	}

	/**
	 * Removes all public files
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	public function deletePublicFiles($component) {
		return $this->deleteFiles('public', $component);
	}

	/**
	 * Removes all internal files
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	public function deleteInternalFiles($component) {
		return $this->deleteFiles('internal', $component);
	}

	/**
	 * Removes all files in a directory
	 *
	 * @param  string $type       'public' or 'internal'
	 * @param  mixed  $component  addOn as string, plugin as array
	 * @return mixed              true if successful, else an error message as a string
	 */
	protected function deleteFiles($type, $component) {
		$dir   = $this->dynFolder($type, $component);
		$state = $this->extend('PRE', 'DELETE_'.strtoupper($type), $component, true);

		if ($state !== true) {
			return $state;
		}

		$obj = new sly_Util_Directory($dir);

		if (!$obj->delete(true)) {
			return t('component_cleanup_failed', $dir);
		}

		return $this->extend('POST', 'DELETE_'.strtoupper($type), $component, true);
	}

	/**
	 * Check if a component is installed and activated
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return boolean           true if available, else false
	 */
	public function isAvailable($component) {
		// If we execute both checks in this order, we avoid the overhead of checking
		// the install status of a disabled addon.
		return $this->isActivated($component) && $this->isInstalled($component);
	}

	/**
	 * Check if a component is installed
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return boolean           true if installed, else false
	 */
	public function isInstalled($component) {
		return $this->getProperty($component, 'install', false) == true;
	}

	/**
	 * Check if a component is activated
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return boolean           true if activated, else false
	 */
	public function isActivated($component) {
		return $this->getProperty($component, 'status', false) == true;
	}

	/**
	 * Get component author
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @param  mixed $default    default value if no author was specified in static.yml
	 * @return mixed             the author as given in static.yml
	 */
	public function getAuthor($component, $default = null) {
		return $this->readConfigValue($component, 'author', $default);
	}

	/**
	 * Get support page
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @param  mixed $default    default value if no page was specified in static.yml
	 * @return mixed             the support page as given in static.yml
	 */
	public function getSupportPage($component, $default = null) {
		return $this->readConfigValue($component, 'supportpage', $default);
	}

	/**
	 * Get version
	 *
	 * This method tries to get the version from the static.yml. If no version is
	 * found, it tries to read the contents of a version file in the component's
	 * directory.
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @param  mixed $default    default value if no version was specified
	 * @return string            the version
	 */
	public function getVersion($component, $default = null) {
		$version     = $this->readConfigValue($component, 'version', null);
		$baseFolder  = $this->baseFolder($component);
		$versionFile = $baseFolder.'/version';

		if ($version === null && file_exists($versionFile)) {
			$version = trim(file_get_contents($versionFile));
		}

		$versionFile = $baseFolder.'/VERSION';

		if ($version === null && file_exists($versionFile)) {
			$version = trim(file_get_contents($versionFile));
		}

		return $version === null ? $default : $version;
	}

	/**
	 * Get last known version
	 *
	 * This method reads the last known version from the local config. This can
	 * be used to determine whether a component has been updated.
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @param  mixed $default    default value if no version was specified
	 * @return string            the version
	 */
	public function getKnownVersion($component, $default = null) {
		$key     = $this->getVersionKey($component);
		$version = sly_Util_Versions::get($key);

		return $version === false ? $default : $version;
	}

	/**
	 * Copy assets from component to it's public folder
	 *
	 * This method copies all files in 'assets' and pipis CSS files through
	 * Scaffold.
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             true if successful, else an error message as a string
	 */
	public function copyAssets($component) {
		$baseDir   = $this->baseFolder($component);
		$assetsDir = sly_Util_Directory::join($baseDir, 'assets');
		$target    = $this->publicFolder($component);

		if (!is_dir($assetsDir)) return true;

		$dir = new sly_Util_Directory($assetsDir);

		if (!$dir->copyTo($target)) {
			return t('component_assets_failed', $assetsDir);
		}

		return true;
	}

	/**
	 * Check if a component version has changed
	 *
	 * This method detects changing versions and tries to include the
	 * update.inc.php if available.
	 *
	 * @param mixed $component  addOn as string, plugin as array
	 */
	public function checkUpdate($component) {
		$version = $this->getVersion($component, false);
		$key     = $this->getVersionKey($component);
		$known   = sly_Util_Versions::get($key, false);

		if ($known !== false && $version !== false && $known !== $version) {
			$updateFile = $this->baseFolder($component).'update.inc.php';

			if (file_exists($updateFile)) {
				$this->req($updateFile);
			}
		}

		if ($version !== false && $known !== $version) {
			sly_Util_Versions::set($key, $version);
		}
	}

	/**
	 * @param  string $file
	 * @return mixed         error message (string) or true
	 */
	private function installDump($file) {
		try {
			$dump = new sly_DB_Dump($file);
			$sql  = sly_DB_Persistence::getInstance();

			foreach ($dump->getQueries(true) as $query) {
				$sql->query($query);
			}
		}
		catch (sly_Exception $e) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * @param  mixed $component
	 * @return mixed             true if OK, else error message (string)
	 */
	private function checkRequirements($component) {
		$requires      = $this->getRequirements($component);
		$aService      = sly_Service_Factory::getAddOnService();
		$pService      = sly_Service_Factory::getPluginService();
		$componentName = $this->buildComponentName($component);

		foreach ($requires as $requiredComponent) {
			$requirement = explode('/', $requiredComponent, 2);

			if (count($requirement) === 1 && !$aService->isAvailable($requirement[0])) {
				return t('component_requires_addon', $requirement[0], $componentName);
			}

			if (count($requirement) === 2 && !$pService->isAvailable($requirement)) {
				return t('component_requires_plugin', $requiredComponent, $componentName);
			}
		}

		return true;
	}

	/**
	 * Returns a list of dependent components
	 *
	 * This method will go through all addOns and plugins and check whether they
	 * require the given component.
	 *
	 * @param  mixed   $component    addOn as string, plugin as array
	 * @param  boolean $onlyMissing  if true, only not available components will be returned
	 * @return array                 a list of components (containing strings for addOns and arrays for plugins)
	 */
	public function getDependencies($component, $onlyMissing = false) {
		return $this->dependencyHelper($component, $onlyMissing);
	}

	/**
	 * Returns a list of dependent components
	 *
	 * This method will go through all addOns and plugins and check whether they
	 * require the given component.
	 *
	 * @param  mixed   $component        addOn as string, plugin as array
	 * @param  boolean $inclDeactivated  if true non-enabled components will be included as well
	 * @return array                     a list of components (containing strings for addOns and arrays for plugins)
	 */
	public function getRecursiveDependencies($component, $inclDeactivated = false) {
		$stack  = $this->dependencyHelper($component, false, false, $inclDeactivated);
		$result = array();

		while (!empty($stack)) {
			$comp  = array_shift($stack);
			$stack = array_merge($stack, $this->dependencyHelper($comp, false, false, $inclDeactivated));
			$stack = array_unique($stack);

			$result[] = $comp;
		}

		return $result;
	}

	/**
	 * Returns a list of dependent components
	 *
	 * This method will go through all addOns and plugins and check whether they
	 * require the given component. The return value will only contain direct
	 * dependencies, it's not recursive.
	 *
	 * @param  mixed   $component        addOn as string, plugin as array
	 * @param  boolean $onlyMissing      if true, only not available components will be returned
	 * @param  boolean $onlyFirst        set this to true if you're only want to know whether a dependency exists
	 * @param  boolean $inclDeactivated  if true non-enabled components will be included as well
	 * @return array                     a list of components (containing strings for addOns and arrays for plugins)
	 */
	public function dependencyHelper($component, $onlyMissing = false, $onlyFirst = false, $inclDeactivated = false) {
		$addonService  = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();
		$addons        = $inclDeactivated ? $addonService->getInstalledAddons() : $addonService->getAvailableAddons();
		$result        = array();
		$compAsString  = $this->buildComponentName($component);

		foreach ($addons as $addon) {
			// don't check yourself
			if ($compAsString === $addon) continue;

			$requires = $addonService->getRequirements($addon, true);
			$inArray  = in_array($compAsString, $requires);
			$visible  = !$onlyMissing || !$addonService->isActivated($addon);

			if ($visible && $inArray) {
				if ($onlyFirst) return array($addon);
				$result[] = $addon;
			}

			$plugins = $inclDeactivated ? $pluginService->getInstalledPlugins($addon) : $pluginService->getAvailablePlugins($addon);

			foreach ($plugins as $plugin) {
				$pComp    = array($addon, $plugin);
				$requires = $pluginService->getRequirements($pComp, true);
				$inArray  = in_array($compAsString, $requires);
				$visible  = !$onlyMissing || !$pluginService->isActivated($pComp);

				if ($visible && $inArray) {
					if ($onlyFirst) return array($pComp);
					$result[] = $pComp;
				}
			}
		}

		return $onlyFirst ? (empty($result) ? '' : reset($result)) : $result;
	}

	/**
	 * Check if a component is required
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return mixed             false if not required, else the first found dependency
	 */
	public function isRequired($component) {
		$dependency = $this->dependencyHelper($component, false, true);
		return empty($dependency) ? false : reset($dependency);
	}

	/**
	 * Return a list of required addOns / plugins
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return array             list of required components
	 */
	public function getRequirements($component, $forceRefresh = false) {
		$req = sly_makeArray($this->readConfigValue($component, 'requires', null, $forceRefresh));

		foreach ($req as $idx => $r) {
			if (strpos($r, '/') !== false) {
				$req[$idx] = explode('/', $r);
			}
		}

		return $req;
	}

	/**
	 * Return a list of Sally versions the component is compatible with
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return array             list of sally versions
	 */
	public function getRequiredSallyVersions($component, $forceRefresh = false) {
		return sly_makeArray($this->readConfigValue($component, 'sally', null, $forceRefresh));
	}

	/**
	 * Check if a component is compatible with this Sally version
	 *
	 * @param  mixed $component  addOn as string, plugin as array
	 * @return boolean           true if compatible, else false
	 */
	public function isCompatible($component, $forceRefresh = false) {
		if (!$forceRefresh) {
			return $this->getProperty($component, 'compatible', false);
		}

		$sallyVersions = $this->getRequiredSallyVersions($component, true);
		$versionOK     = false;

		foreach ($sallyVersions as $version) {
			$versionOK |= $this->checkVersion($version);
		}

		return (boolean) $versionOK;
	}

	protected function clearLoadCache() {
		sly_Core::cache()->delete('sly', 'componentorder');
		sly_Core::cache()->delete('sly', 'availableaddons');
	}

	public function loadComponents() {
		// Make sure we don't accidentally load components that have become
		// incompatible due to Sally and/or component updates.
		$changes = $this->deactivateIncompatibleComponents();

		$cache         = sly_Core::cache();
		$prodMode      = !sly_Core::isDeveloperMode();
		$order         = (!$changes && $prodMode) ? $cache->get('sly', 'componentorder') : null;
		$addonService  = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();

		// if there is no cache yet, we load all components the slow way
		if (!is_array($order)) {
			// reset our helper to keep track of the component stati
			self::$loadInfo = array();

			foreach ($addonService->getRegisteredAddons() as $addonName) {
				$addonService->load($addonName);

				foreach ($pluginService->getRegisteredPlugins($addonName) as $pluginName) {
					$pluginService->load(array($addonName, $pluginName));
				}
			}

			// and now we have a nice list in self::$loadInfo that we can cache
			$cache->set('sly', 'componentorder', self::$loadInfo);
		}

		// yay, a cache, let's skip the whole dependency stuff
		else {
			foreach ($order as $name => $info) {
				list($component, $installed, $activated) = $info;

				$service = is_array($component) ? $pluginService : $addonService;

				// load component config files
				$service->loadConfig($component, $installed, $activated);

				// init the component
				if ($activated) {
					$configFile = $service->baseFolder($component).'config.inc.php';
					$service->req($configFile);

					self::$loaded[$name] = $component;
				}
			}
		}
	}

	/**
	 * @param mixed   $component  addOn as string, plugin as array
	 * @param boolean $force      load the component even if it's not active
	 */
	protected function load($component, $force = false) {
		$compAsString = $this->buildComponentName($component);

		if (isset(self::$loaded[$compAsString])) {
			return true;
		}

		$service = $this->getService($component);

		if (!$service->exists($component)) {
			trigger_error('Component '.$compAsString.' does not exists.', E_USER_WARNING);

			sly_Core::cache()->flush('sly.staticyml');
			$this->clearLoadCache();

			return false;
		}

		$compatible = $this->isCompatible($component);
		$activated  = $compatible && $this->isAvailable($component);
		$installed  = $compatible && ($activated || $this->isInstalled($component));

		if ($installed || $force) {
			$this->loadConfig($component, $installed, $activated);
			self::$loadInfo[$compAsString] = array($component, $installed, $activated);

			// TODO: remove this magic in next (0.7) release
			$page = $this->getProperty($component, 'page', '');
			$name = $this->getProperty($component, 'name', '');

			if (!empty($page)) {
				sly_Core::config()->set('authorisation/pages/token', array($page => $name));
			}
		}

		if ($activated || $force) {
			$requires = $service->getProperty($component, 'requires');

			if (!empty($requires)) {
				if (!is_array($requires)) $requires = sly_makeArray($requires);

				foreach ($requires as $required) {
					$required = explode('/', $required, 2);

					// first load the addon
					$this->load($required[0], $force);

					// then the plugin
					if (count($required) === 2) {
						$this->load($required, $force);
					}
				}
			}

			$this->checkUpdate($component);

			$configFile = $this->baseFolder($component).'config.inc.php';
			$this->req($configFile);

			self::$loaded[$compAsString] = $component;
		}
	}

	/**
	 * Read a config value directly (without using the config system)
	 *
	 * @param  mixed  $component  addOn as string, plugin as array
	 * @param  string $key        array key
	 * @param  mixed  $default    value if key is not set
	 * @return mixed              value or default
	 */
	private function readConfigValue($component, $key, $default = null, $forceRefresh = false) {
		// To make this method work on components that are not yet installed or
		// activated, we have to get their static.yml's content on our own. The
		// project config at this point already contains 'empty' information for
		// the component and would not the static.yml via loadStatic().

		if (!$forceRefresh && $this->isAvailable($component)) {
			return $this->getService($component)->getProperty($component, $key, $default);
		}

		$file = $this->baseFolder($component).'static.yml';
		if (!file_exists($file)) return $default; // bad component

		$cache = sly_Core::cache();
		$ckey  = md5($file);
		$mtime = filemtime($file);
		$data  = $cache->get('sly.staticyml', $ckey, null);

		if (!is_array($data) || $data['mtime'] != $mtime) {
			$config = sly_Util_YAML::load($file);
			$data   = array('mtime' => $mtime, 'config' => $config);

			$cache->set('sly.staticyml', $ckey, $data);
		}

		$config = $data['config'];
		return isset($config[$key]) ? $config[$key] : $default;
	}

	private function getService($component) {
		return is_array($component) ? sly_Service_Factory::getPluginService() : sly_Service_Factory::getAddOnService();
	}
}
