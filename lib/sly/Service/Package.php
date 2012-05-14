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
class sly_Service_Package {
	protected $sourceDir; ///< string
	protected $dynDir;    ///< string

	const SALLY_PKGKEY     = 'sallycms/sallycms';
	const INSTALLER_PKGKEY = 'sallycms/composer-installer';

	/**
	 * @param string $sourceDir
	 * @param string $dynDir
	 */
	public function __construct($sourceDir, $dynDir) {
		$this->sourceDir = sly_Util_Directory::normalize($sourceDir).DIRECTORY_SEPARATOR;
		$this->dynDir    = sly_Util_Directory::normalize($dynDir).DIRECTORY_SEPARATOR;
	}

	/**
	 * @param  string $package   package name
	 * @return string
	 */
	public function baseDirectory($package = null) {
		$dir = $this->sourceDir;

		if (!empty($package)) {
			$dir .= str_replace('/', DIRECTORY_SEPARATOR, $package).DIRECTORY_SEPARATOR;
		}

		return $dir;
	}

	/**
	 * Sets a package's property
	 *
	 * Properties are stored in the project configuration as keys within
	 * 'packages'.
	 *
	 * @param  string $package   package name
	 * @param  string $property  property name
	 * @param  mixed  $value     property value
	 * @return mixed             the set value
	 */
	public function setProperty($package, $property, $value) {
		$this->clearCache();
		return sly_Core::config()->set($this->getConfPath($package).'/'.$property, $value);
	}

	/**
	 * Gets a package's property
	 *
	 * @param  string $package   package
	 * @param  string $property  property name
	 * @param  mixed  $default   fallback value if property was not found
	 * @return string            found value or $default
	 */
	public function getProperty($package, $property, $default = null) {
		return sly_Core::config()->get($this->getConfPath($package).'/'.$property, $default);
	}

	/**
	 * @param  string $type     'internal' or 'public'
	 * @param  string $package  package name
	 * @return string
	 */
	protected function dynDirectory($type, $package) {
		$dir = $this->dynDir.$type.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $package);
		return sly_Util_Directory::create($dir);
	}

	/**
	 * @param  string $package  package name
	 * @return string           e.g. 'package/vendor_package'
	 */
	public function getVersionKey($package) {
		return 'package/'.str_replace('/', '_', $package);
	}

	/**
	 * @param  string $package  package name
	 * @return string           e.g. 'packages/vendor:package'
	 */
	public function getConfPath($package) {
		return 'packages/'.str_replace('/', ':', $package);
	}

	/**
	 * @param  string $package  package name
	 * @return boolean
	 */
	public function exists($package) {
		$base = $this->baseDirectory($package);
		return file_exists($base.'composer.json');
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
	 * Adds a new package to the global config
	 *
	 * @param string $package  package name
	 */
	public function add($package) {
		$this->setProperty($package, 'install', false);
		$this->setProperty($package, 'status', false);
		$this->clearCache();
	}

	/**
	 * Removes a package from the global config
	 *
	 * @param string $package  package name
	 */
	public function remove($package) {
		sly_Core::config()->remove($this->getConfPath($package));
		$this->clearCache();
	}

	/**
	 * Get the full path to the public directory
	 *
	 * @param  string $package  package name
	 * @return string           full path
	 */
	public function publicDirectory($package) {
		return $this->dynDirectory('public', $package);
	}

	/**
	 * Get the full path to the internal directory
	 *
	 * @param  string $package  package name
	 * @return string           full path
	 */
	public function internalDirectory($package) {
		return $this->dynDirectory('internal', $package);
	}

	/**
	 * Removes all public files
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	public function deletePublicFiles($package) {
		return $this->deleteFiles('public', $package);
	}

	/**
	 * Removes all internal files
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	public function deleteInternalFiles($package) {
		return $this->deleteFiles('internal', $package);
	}

	/**
	 * @param  string  $time     'PRE' or 'POST'
	 * @param  string  $type     'INSTALL', 'ACTIVATE', ...
	 * @param  string  $package  package name
	 * @param  boolean $filter   true to let the listeners filter the value 'true' or false to just notify them
	 * @return boolean           always true if $filter=false, else the result of the filter event
	 */
	public function fireEvent($time, $type, $package, $filter) {
		$event      = 'SLY_PACKAGE_'.$time.'_'.$type;
		$params     = array('package' => $package);
		$dispatcher = sly_Core::dispatcher();

		if ($filter) {
			return $dispatcher->filter($event, true, $params);
		}

		$dispatcher->notify($event, true, $params);
		return true;
	}

	/**
	 * Removes all files in a directory
	 *
	 * @param  string $type     'public' or 'internal'
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	protected function deleteFiles($type, $package) {
		$dir   = $this->dynDirectory($type, $package);
		$state = $this->fireEvent('PRE', 'DELETE_'.strtoupper($type), $package, true);

		if ($state !== true) {
			return $state;
		}

		$obj = new sly_Util_Directory($dir);

		if (!$obj->delete(true)) {
			return t('package_cleanup_failed', $dir);
		}

		return $this->extend('POST', 'DELETE_'.strtoupper($type), $package, true);
	}

	/**
	 * Check if a package is registered
	 *
	 * @param  string $package  package name
	 * @return boolean          true if registered, else false
	 */
	public function isRegistered($package) {
		return $this->getProperty($package, 'install', false) === null;
	}

	/**
	 * Check if a package is installed and activated
	 *
	 * @param  string $package  package name
	 * @return boolean          true if available, else false
	 */
	public function isAvailable($package) {
		// If we execute both checks in this order, we avoid the overhead of checking
		// the install status of a disabled addon.
		return $this->isActivated($package) && $this->isInstalled($package);
	}

	/**
	 * Check if a package is installed
	 *
	 * @param  string $package  package name
	 * @return boolean          true if installed, else false
	 */
	public function isInstalled($package) {
		return $this->getProperty($package, 'install', false) == true;
	}

	/**
	 * Check if a package is activated
	 *
	 * @param  string $package  package name
	 * @return boolean          true if activated, else false
	 */
	public function isActivated($package) {
		return $this->getProperty($package, 'status', false) == true;
	}

	/**
	 * Get package author
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no author was specified in composer.json
	 * @return mixed            the author as given in static.yml
	 */
	public function getAuthor($package, $default = null) {
		return $this->getComposerKey($package, 'author', $default);
	}

	/**
	 * Get support page
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no page was specified in composer.json
	 * @return mixed            the support page as given in static.yml
	 */
	public function getHomepage($package, $default = null) {
		return $this->getComposerKey($package, 'homepage', $default);
	}

	/**
	 * Get parent package (only relevant for backend list)
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no page was specified in composer.json
	 * @return mixed            the parent package or null if not given
	 */
	public function getParent($package) {
		return $this->getComposerKey($package, 'parent', null);
	}

	/**
	 * Get version
	 *
	 * This method tries to get the version from the composer.json. If no version
	 * is found, it tries to read the contents of a version file in the
	 * package's directory.
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no version was specified
	 * @return string           the version
	 */
	public function getVersion($package, $default = null) {
		$version     = $this->getComposerKey($package, 'version', null);
		$baseDir     = $this->baseDirectory($package);
		$versionFile = $baseDir.'/version';

		if ($version === null && file_exists($versionFile)) {
			$version = trim(file_get_contents($versionFile));
		}

		$versionFile = $baseDir.'/VERSION';

		if ($version === null && file_exists($versionFile)) {
			$version = trim(file_get_contents($versionFile));
		}

		return $version === null ? $default : $version;
	}

	/**
	 * Get last known version
	 *
	 * This method reads the last known version from the local config. This can
	 * be used to determine whether a package has been updated.
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no version was specified
	 * @return string           the version
	 */
	public function getKnownVersion($package, $default = null) {
		$key     = $this->getVersionKey($package);
		$version = sly_Util_Versions::get($key);

		return $version === false ? $default : $version;
	}

	/**
	 * Copy assets from package to it's public folder
	 *
	 * This method copies all files in 'assets' to the public directory of the
	 * given package.
	 *
	 * @param  string $package  package name
	 * @return mixed            true if successful, else an error message as a string
	 */
	public function copyAssets($package) {
		$baseDir   = $this->baseDirectory($package);
		$assetsDir = $baseDir.'assets';
		$target    = $this->publicDirectory($package);

		if (!is_dir($assetsDir)) {
			return true;
		}

		$dir = new sly_Util_Directory($assetsDir);

		if (!$dir->copyTo($target)) {
			return t('package_assets_failed', $assetsDir);
		}

		return true;
	}

	/**
	 * Returns a list of dependent packages
	 *
	 * This method will go through all packages and check whether they require
	 * the given package.
	 *
	 * @param  string  $package      package name
	 * @param  boolean $onlyMissing  if true, only not available packages will be returned
	 * @return array                 a list of packages
	 */
	public function getDependencies($package, $onlyMissing = false) {
		return $this->dependencyHelper($package, $onlyMissing);
	}

	/**
	 * Gives an array of known packages
	 *
	 * @return array  list of packages
	 */
	public function getRegisteredPackages() {
		$config   = sly_Core::config();
		$packages = array_keys($config->get('packages', array()));

		foreach ($packages as $idx => $pkg) {
			$packages[$idx] = str_replace(':', '/', $pkg);
		}

		natcasesort($packages);
		return $packages;
	}

	/**
	 * Gives an array of available packages
	 *
	 * @return array  list of packages
	 */
	public function getAvailablePackages() {
		return $this->filterByProperty('status');
	}

	/**
	 * Gives an array of installed packages
	 *
	 * @return array  list of packages
	 */
	public function getInstalledPackages() {
		return $this->filterByProperty('install');
	}

	/**
	 * @return array  list of packages
	 */
	protected function filterByProperty($property) {
		$cache  = sly_Core::cache();
		$key    = 'filter_'.$property;
		$result = $cache->get('sly.packages', $key);

		if (!is_array($result)) {
			$result = array();

			foreach ($this->getRegisteredPackages() as $pkg) {
				if ($this->getProperty($pkg, $property)) $result[] = $pkg;
			}

			$cache->set('sly.packages', $key, $result);
		}

		return $result;
	}

	/**
	 * Returns a list of dependent packages
	 *
	 * This method will go through all packages and check whether they
	 * require the given package.
	 *
	 * @param  string  $package          package name
	 * @param  boolean $inclDeactivated  if true non-enabled packages will be included as well
	 * @return array                     a list of packages
	 */
	public function getRecursiveDependencies($package, $inclDeactivated = false) {
		$stack  = $this->dependencyHelper($package, false, false, $inclDeactivated);
		$result = array();

		while (!empty($stack)) {
			$pkg   = array_shift($stack);
			$stack = array_merge($stack, $this->dependencyHelper($pkg, false, false, $inclDeactivated));
			$stack = array_unique($stack);

			$result[] = $pkg;
		}

		return $result;
	}

	/**
	 * Returns a list of dependent packages
	 *
	 * This method will go through all addOns and plugins and check whether they
	 * require the given package. The return value will only contain direct
	 * dependencies, it's not recursive.
	 *
	 * @param  string  $package          package name
	 * @param  boolean $onlyMissing      if true, only not available packages will be returned
	 * @param  boolean $onlyFirst        set this to true if you're only want to know whether a dependency exists
	 * @param  boolean $inclDeactivated  if true non-enabled packages will be included as well
	 * @return array                     a list of packages
	 */
	public function dependencyHelper($package, $onlyMissing = false, $onlyFirst = false, $inclDeactivated = false) {
		$packages = $inclDeactivated ? $this->getInstalledPackages(null, true) : $this->getAvailablePackages(null, true);
		$result   = array();

		foreach ($packages as $curPkg) {
			// don't check yourself
			if ($package === $curPkg) continue;

			$requires = $this->getRequirements($curPkg);
			$inArray  = in_array($name, $requires);
			$visible  = !$onlyMissing || !$this->isActivated($curPkg);

			if ($visible && $inArray) {
				if ($onlyFirst) return array($curPkg);
				$result[] = $curPkg;
			}
		}

		return $onlyFirst ? (empty($result) ? '' : reset($result)) : $result;
	}

	/**
	 * Check if a package is required
	 *
	 * @param  string $package  package name
	 * @return mixed            false if not required, else the first found dependency
	 */
	public function isRequired($package) {
		$dependency = $this->dependencyHelper($package, false, true);
		return empty($dependency) ? false : reset($dependency);
	}

	/**
	 * Return a list of required packages
	 *
	 * @param  string  $package  package name
	 * @param  boolean $full     if false, the method returns the package names only, if true, it will include the version constraint
	 * @return array             list of required packages
	 */
	public function getRequirements($package, $full = false) {
		$requirements = $this->getComposerKey($package, 'require', array());

		// filter out 'sallycms/composer-installer'
		unset($requirements[self::INSTALLER_PKGKEY]);

		return $full ? $requirements : array_keys($requirements);
	}

	/**
	 * Return a list of Sally versions the package is compatible with
	 *
	 * @param  string $package  package name
	 * @return array            list of sally versions (can be empty for badly defined composer.json files)
	 */
	public function getRequiredSallyVersions($package) {
		$requirements = $this->getRequirements($package, true);

		// nothing given
		if (!isset($requirements[self::SALLY_PKGKEY])) return array();

		// split up the Composer-style definition
		return explode(',', $requirements[self::SALLY_PKGKEY]);
	}

	/**
	 * Check if a package is compatible with this Sally version
	 *
	 * @param  string $package  package name
	 * @return boolean          true if compatible, else false
	 */
	public function isCompatible($package) {
		$sallyVersions = $this->getRequiredSallyVersions($package);

		foreach ($sallyVersions as $version) {
			if ($this->checkVersion($version)) return true;
		}

		return false;
	}

	protected function clearCache() {
		sly_Core::cache()->flush('sly.packages');
	}

	/**
	 * Read a config value from the composer.json
	 *
	 * @param  string $package  package name
	 * @param  string $key      array key
	 * @param  mixed  $default  value if key is not set
	 * @return mixed            value or default
	 */
	public function getComposerKey($package, $key, $default = null) {
		$composer = $this->baseDirectory($package).'composer.json';
		$value    = sly_Util_Composer::getKey($composer, $key);

		return $value === null ? $default : $value;
	}
}
