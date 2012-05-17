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
class sly_Service_AddOn {
	protected $pkgService; ///< sly_Service_Package
	protected $dynDir;     ///< string

	const SALLY_PKGKEY     = 'sallycms/sallycms';
	const INSTALLER_PKGKEY = 'sallycms/composer-installer';

	/**
	 * @param sly_Service_Package $pkgService
	 * @param string              $dynDir
	 */
	public function __construct(sly_Service_Package $pkgService, $dynDir) {
		$this->pkgService = $pkgService;
		$this->dynDir     = sly_Util_Directory::normalize($dynDir).DIRECTORY_SEPARATOR;
	}

	/**
	 * @return sly_Service_Package
	 */
	public function getPackageService() {
		return $this->pkgService;
	}

	/**
	 * Sets a addon's property
	 *
	 * Properties are stored in the project configuration as keys within
	 * 'addons'.
	 *
	 * @param  string $addon     addon name
	 * @param  string $property  property name
	 * @param  mixed  $value     property value
	 * @return mixed             the set value
	 */
	public function setProperty($addon, $property, $value) {
		$this->clearCache();
		return sly_Core::config()->set($this->getConfPath($addon).'/'.$property, $value);
	}

	/**
	 * Gets a addon's property
	 *
	 * @param  string $addon     addon
	 * @param  string $property  property name
	 * @param  mixed  $default   fallback value if property was not found
	 * @return string            found value or $default
	 */
	public function getProperty($addon, $property, $default = null) {
		return sly_Core::config()->get($this->getConfPath($addon).'/'.$property, $default);
	}

	/**
	 * @param  string $type   'internal' or 'public'
	 * @param  string $addon  addon name
	 * @return string
	 */
	public function dynDirectory($type, $addon) {
		$dir = $this->dynDir.$type.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $addon);
		return sly_Util_Directory::create($dir);
	}

	/**
	 * @param  string $addon  addon name
	 * @return string         e.g. 'addons/vendor:addon'
	 */
	public function getConfPath($addon) {
		return 'addons/'.str_replace('/', ':', $addon);
	}

	/**
	 * @param  string  $property       property name
	 * @param  mixed   $value          value to match
	 * @param  boolean $isComposerKey  true to search in composer.json instead of project config
	 * @return array                   list of addOns
	 */
	public function filterByProperty($property, $value = true, $isComposerKey = false) {
		$cache  = sly_Core::cache();
		$key    = sly_Cache::generateKey('filter', $property, $value, $isComposerKey);
		$result = $cache->get('sly.addon', $key);

		if (!is_array($result)) {
			$result = array();

			foreach ($this->getRegisteredAddOns() as $addon) {
				if ($isComposerKey) {
					$found = $this->getComposerKey($addon, $property);
				}
				else {
					$found = $this->getProperty($addon, $property);
				}

				if ($found === $value) $result[] = $addon;
			}

			$cache->set('sly.addon', $key, $result);
		}

		return $result;
	}

	/**
	 * Gives an array of known addOns
	 *
	 * @return array  list of addOns
	 */
	public function getRegisteredAddOns() {
		$config = sly_Core::config();
		$addons = array_keys($config->get('addons', array()));

		foreach ($addons as $idx => $pkg) {
			$addons[$idx] = str_replace(':', '/', $pkg);
		}

		natcasesort($addons);
		return $addons;
	}

	/**
	 * Gives an array of available addOns
	 *
	 * @return array  list of addOns
	 */
	public function getAvailableAddOns() {
		return $this->filterByProperty('status');
	}

	/**
	 * Gives an array of installed addOns
	 *
	 * @return array  list of addOns
	 */
	public function getInstalledAddOns() {
		return $this->filterByProperty('install');
	}

	/**
	 * Check if a addOn is required
	 *
	 * @param  string $addon  addOn name
	 * @return mixed          false if not required, else the first found dependency
	 */
	public function isRequired($addon) {
		$dependencies = $this->getDependencies($addon, false, true);
		return empty($dependencies) ? false : reset($dependencies);
	}

	/**
	 * Return a list of required addOns
	 *
	 * @param  string $addon  addOn name
	 * @return array          list of required addOns
	 */
	public function getRequirements($addon) {
		$ignore = array(self::INSTALLER_PKGKEY, self::SALLY_PKGKEY);
		return $this->pkgService->getRequirements($addon, true, $ignore);
	}

	/**
	 * Return a list of dependent packages
	 *
	 * Dependent packages are packages that need $addon to run.
	 *
	 * @param  string  $addon       addOn name
	 * @param  boolean $recursive   if true, dependencies are search resursively
	 * @param  boolean $activeOnly  if true, non-available addOns are removed from the result list
	 * @return array                list of required packages
	 */
	public function getDependencies($addon, $recursive = true, $activeOnly = false) {
		$deps = $this->pkgService->getDependencies($addon, $recursive);

		if ($activeOnly) {
			// remove non-active addOns from the list of dependencies
			foreach ($deps as $idx => $dep) {
				if (!$this->isAvailable($dep)) {
					unset($deps[$idx]);
				}
			}

			$deps = array_values($deps);
		}

		return $deps;
	}

	/**
	 * Return a list of Sally versions the addOn is compatible with
	 *
	 * @param  string $addon  addOn name
	 * @return array          list of sally versions (can be empty for badly defined composer.json files)
	 */
	public function getRequiredSallyVersions($addon) {
		$requirements = $this->pkgService->getKey($addon, 'require', array());

		// nothing given
		if (!isset($requirements[self::SALLY_PKGKEY])) return array();

		// split up the Composer-style definition
		return explode(',', $requirements[self::SALLY_PKGKEY]);
	}

	/**
	 * Check if an addon is compatible with this Sally version
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if compatible, else false
	 */
	public function isCompatible($addon) {
		$sallyVersions = $this->getRequiredSallyVersions($addon);

		foreach ($sallyVersions as $version) {
			if ($this->checkVersion($version)) return true;
		}

		return false;
	}

	/**
	 * Check if a version number matches
	 *
	 * This will take a well-formed version number (X.Y.Z) and compare it against
	 * the system version. You can leave out parts from the right to make it
	 * more general (i.e. '0.2' will match any 0.2.x version).
	 *
	 * @param  string $version  the version number to check against
	 * @param  string $ref      reference version (null uses the Sally version)
	 * @return boolean          true for a match, else false
	 */
	public function checkVersion($version, $ref = null) {
		$thisVersion = $ref === null ? sly_Core::getVersion('X.Y.Z') : $ref;
		return preg_match('#^'.preg_quote($version, '#').'.*#i', $thisVersion) == 1;
	}

	/**
	 * Get the full path to the public directory
	 *
	 * @param  string $addon  addon name
	 * @return string         full path
	 */
	public function publicDirectory($addon) {
		return $this->dynDirectory('public', $addon);
	}

	/**
	 * Get the full path to the internal directory
	 *
	 * @param  string $addon  addon name
	 * @return string         full path
	 */
	public function internalDirectory($addon) {
		return $this->dynDirectory('internal', $addon);
	}

	/**
	 * Check if an addon is registered
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if registered, else false
	 */
	public function isRegistered($addon) {
		return $this->getProperty($addon, 'install', null) !== null;
	}

	/**
	 * Check if an addon is installed and activated
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if available, else false
	 */
	public function isAvailable($addon) {
		// If we execute both checks in this order, we avoid the overhead of checking
		// the install status of a disabled addon.
		return $this->isActivated($addon) && $this->isInstalled($addon);
	}

	/**
	 * Check if an addon is installed
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if installed, else false
	 */
	public function isInstalled($addon) {
		return $this->getProperty($addon, 'install', false) == true;
	}

	/**
	 * Check if an addon is activated
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if activated, else false
	 */
	public function isActivated($addon) {
		return $this->getProperty($addon, 'status', false) == true;
	}

	/**
	 * Clears the addOn metadata cache
	 */
	public function clearCache() {
		sly_Core::cache()->flush('sly.addon', true);
	}

	/**
	 * Read a config value from the composer.json
	 *
	 * @param  string $addon    addon name
	 * @param  string $key      array key
	 * @param  mixed  $default  value if key is not set
	 * @return mixed            value or default
	 */
	public function getComposerKey($addon, $key, $default = null) {
		return $this->pkgService->getKey($addon, $key, $default);
	}
}
