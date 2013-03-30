<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use sly\Filesystem\Decorator\Prefixed;
use sly\Filesystem\Filesystem;

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_AddOn {
	protected $config;     ///< sly_Configuration
	protected $cache;      ///< BabelCache_Interface
	protected $pkgService; ///< sly_Service_Package
	protected $vndService; ///< sly_Service_Package  vendor package service (optional)
	protected $publicFs;   ///< Filesystem
	protected $internalFs; ///< Filesystem

	const SALLY_PKGKEY     = 'sallycms/sallycms';
	const INSTALLER_PKGKEY = 'sallycms/composer-installer';

	/**
	 * @param sly_Configuration    $config
	 * @param BabelCache_Interface $cache
	 * @param sly_Service_Package  $pkgService
	 * @param Filesystem           $publicFs
	 * @param Filesystem           $internalFs
	 */
	public function __construct(sly_Configuration $config, BabelCache_Interface $cache, sly_Service_Package $pkgService, Filesystem $publicFs, Filesystem $internalFs) {
		$this->config     = $config;
		$this->cache      = $cache;
		$this->pkgService = $pkgService;
		$this->publicFs   = $publicFs;
		$this->internalFs = $internalFs;
	}

	/**
	 * @param sly_Service_Package $service
	 */
	public function setVendorPackageService(sly_Service_Package $service) {
		$this->vndService = $service;
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
		return $this->config->set($this->getConfPath($addon).'/'.$property, $value);
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
		return $this->config->get($this->getConfPath($addon).'/'.$property, $default);
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
		$cache  = $this->cache;
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
		$config = $this->config;
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
	 * @param  string  $addon      addOn name
	 * @param  boolean $recursive  whether or not to fetch the requirement's requirements
	 * @return array               list of required addOns
	 */
	public function getRequirements($addon, $recursive = true) {
		$ignore = array(self::INSTALLER_PKGKEY, self::SALLY_PKGKEY);

		// filter out vendor packages
		if ($this->vndService) {
			$vendors = $this->vndService->getPackages();
			$ignore  = array_merge($ignore, $vendors);
		}

		return $this->pkgService->getRequirements($addon, $recursive, $ignore);
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
	 * @return string         the version constraint like ">=0.7,<0.9" or null if nothing is defined
	 */
	public function getRequiredSallyVersion($addon) {
		$requirements = $this->pkgService->getKey($addon, 'require', array());
		return isset($requirements[self::SALLY_PKGKEY]) ? $requirements[self::SALLY_PKGKEY] : null;
	}

	/**
	 * Check if an addon is compatible with this Sally version
	 *
	 * @param  string $addon  addon name
	 * @return boolean        true if compatible, else false
	 */
	public function isCompatible($addon) {
		$version = $this->getRequiredSallyVersion($addon);
		return sly_Util_Versions::isCompatible($version);
	}

	/**
	 * Get the filesystem containing an addOn's public files
	 *
	 * @param  string $addon  addon name
	 * @return Filesystem
	 */
	public function publicFilesystem($addon) {
		return new Prefixed($this->publicFs, $addon);
	}

	/**
	 * Get the filesystem containing an addOn's internal files
	 *
	 * @param  string $addon  addon name
	 * @return Filesystem
	 */
	public function internalFilesystem($addon) {
		return new Prefixed($this->internalFs, $addon);
	}

	/**
	 * Checks if an addOn exists
	 *
	 * @param  string $addon  addon name
	 * @return boolean
	 */
	public function exists($addon) {
		return $this->pkgService->exists($addon);
	}

	/**
	 * get addOn version
	 *
	 * @param  string $addon  addon name
	 * @return string
	 */
	public function getVersion($addon) {
		return $this->pkgService->getVersion($addon);
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
		$this->cache->flush('sly.addon', true);
		$this->pkgService->clearCache();
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
