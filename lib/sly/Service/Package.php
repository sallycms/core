<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	protected $cache;     ///< BabelCache_Interface
	protected $composers; ///< array
	protected $refreshed; ///< boolean
	protected $namespace; ///< string

	/**
	 * @param string               $sourceDir
	 * @param BabelCache_Interface $cache
	 */
	public function __construct($sourceDir, BabelCache_Interface $cache) {
		$this->sourceDir = sly_Util_Directory::normalize($sourceDir).DIRECTORY_SEPARATOR;
		$this->cache     = $cache;
		$this->composers = array();
		$this->refreshed = false;
		$this->namespace = substr(md5($this->sourceDir), 0, 10).'_';
	}

	/**
	 * Clears the addOn metadata cache
	 */
	public function clearCache() {
		$this->cache->flush('sly.package', true);
		$this->composers = array();
		$this->refreshed = false;
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

	protected function getCacheKey($package, $key) {
		$package = str_replace('/', '%', $package);
		return ($package ? $package.'%' : '').$key;
	}

	protected function getCache($package, $key, $default = null) {
		return $this->cache->get('sly.package.'.$this->namespace, $this->getCacheKey($package, $key), $default);
	}

	protected function setCache($package, $key, $value) {
		return $this->cache->set('sly.package.'.$this->namespace, $this->getCacheKey($package, $key), $value);
	}

	/**
	 * @param  string  $package       package name
	 * @param  boolean $forceRefresh  true to not use the cache and check if the composer.json is present
	 * @return boolean
	 */
	public function exists($package, $forceRefresh = false) {
		$exists = $forceRefresh ? null : $this->getCache($package, 'exists');

		if ($exists === null) {
			$base   = $this->baseDirectory($package);
			$exists = file_exists($base.'composer.json');

			$this->setCache($package, 'exists', $exists);
		}

		return $exists;
	}

	/**
	 * Get package author
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no author was specified in composer.json
	 * @return mixed            the author as given in static.yml
	 */
	public function getAuthor($package, $default = null) {
		$authors = $this->getKey($package, 'authors', null);
		if (!is_array($authors) || empty($authors)) return $default;

		$first = reset($authors);
		return isset($first['name']) ? $first['name'] : $default;
	}

	/**
	 * Get support page
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no page was specified in composer.json
	 * @return mixed            the support page as given in static.yml
	 */
	public function getHomepage($package, $default = null) {
		return $this->getKey($package, 'homepage', $default);
	}

	/**
	 * Get parent package (only relevant for backend list)
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no page was specified in composer.json
	 * @return mixed            the parent package or null if not given
	 */
	public function getParent($package) {
		return $this->getKey($package, 'parent', null);
	}

	/**
	 * Get children packages (only relevant for backend list)
	 *
	 * @param  string $package  parent package name
	 * @return array
	 */
	public function getChildren($parent) {
		$packages = $this->getPackages();
		$children = array();

		foreach ($packages as $package) {
			if ($this->getParent($package) === $parent) {
				$children[] = $package;
			}
		}

		return $children;
	}

	/**
	 * Get version
	 *
	 * @param  string $package  package name
	 * @param  mixed  $default  default value if no version was specified
	 * @return string           the version
	 */
	public function getVersion($package, $default = null) {
		return $this->getKey($package, 'version', $default);
	}

	/**
	 * @param  string $package  package name
	 * @return string           e.g. 'package/vendor:package'
	 */
	public function getVersionKey($package) {
		return 'package/'.str_replace('/', ':', $package);
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
	 * Set last known version
	 *
	 * @param  string $package  package name
	 * @param  string $version  new version
	 * @return string           the version
	 */
	public function setKnownVersion($package, $version) {
		$key = $this->getVersionKey($package);
		return sly_Util_Versions::set($key, $version);
	}

	/**
	 * Read a config value from the composer.json
	 *
	 * @param  string $package  package name
	 * @param  string $key      array key
	 * @param  mixed  $default  value if key is not set
	 * @return mixed            value or default
	 */
	public function getKey($package, $key, $default = null) {
		if (!isset($this->composers[$package])) {
			$composer = $this->getCache($package, 'composer.json');

			if ($composer === null) {
				$filename = $this->baseDirectory($package).'composer.json';
				$composer = new sly_Util_Composer($filename);

				$composer->setPackage($package);
				$composer->getContent($this->baseDirectory().'composer'); // read file

				$this->setCache($package, 'composer.json', $composer);
			}
			elseif (sly_Core::isDeveloperMode() && $composer->revalidate()) {
				$this->setCache($package, 'composer.json', $composer);
			}

			$this->composers[$package] = $composer;
		}

		$value = $this->composers[$package]->getKey($key);
		return $value === null ? $default : $value;
	}

	/**
	 * Return a list of required packages
	 *
	 * Required packages are packages that $package itself needs to run.
	 *
	 * @param  string  $package    package name
	 * @param  boolean $recursive  if true, requirements are search recursively
	 * @param  array   $ignore     list of packages to ignore (and not recurse into)
	 * @return array               list of required packages
	 */
	public function getRequirements($package, $recursive = true, array $ignore = array()) {
		$cacheKey = 'requirements_'.($recursive ? 1 : 0);
		$result   = $this->getCache($package, $cacheKey);

		if ($result !== null) {
			// apply ignore list
			foreach ($ignore as $i) {
				$idx = array_search($i, $result);
				if ($idx !== false) unset($result[$idx]);
			}

			return array_values($result);
		}

		$stack  = array($package);
		$stack  = array_merge($stack, array_keys($this->getKey($package, 'require', array())));
		$result = array();

		// don't add self
		$ignore[] = $package;

		do {
			// take one out
			$pkg = array_shift($stack);

			// add its requirements
			if ($this->exists($pkg) && $recursive) {
				$stack = array_merge($stack, array_keys($this->getKey($pkg, 'require', array())));
				$stack = array_unique($stack);
			}

			// filter out non-packages
			foreach ($stack as $idx => $req) {
				if (strpos($req, '/') === false) unset($stack[$idx]);
			}

			// respect ignore list
			foreach ($ignore as $i) {
				$idx = array_search($i, $stack);
				if ($idx !== false) unset($stack[$idx]);
			}

			// do not add $package itself or duplicates
			if ($pkg !== $package && !in_array($pkg, $result)) {
				$result[] = $pkg;
			}
		}
		while (!empty($stack));

		natcasesort($result);
		$this->setCache($package, $cacheKey, $result);

		return $result;
	}

	/**
	 * Return a list of dependent packages
	 *
	 * Dependent packages are packages that need $package to run.
	 *
	 * @param  string  $package    package name
	 * @param  boolean $recursive  if true, dependencies are search recursively
	 * @return array               list of required packages
	 */
	public function getDependencies($package, $recursive = true) {
		$cacheKey = 'dependencies_'.($recursive ? 1 : 0);
		$result   = $this->getCache($package, $cacheKey);

		if ($result !== null) {
			return $result;
		}

		$all    = $this->getPackages();
		$stack  = array($package);
		$result = array();

		do {
			// take one out
			$pkg = array_shift($stack);

			// find packages requiering $pkg
			foreach ($all as $p) {
				$requirements = array_keys($this->getKey($p, 'require', array()));

				if (in_array($pkg, $requirements)) {
					$result[] = $p;
					$stack[]  = $p;
				}
			}

			$stack = array_unique($stack);
		}
		while ($recursive && !empty($stack));

		$result = array_unique($result);
		natcasesort($result);
		$this->setCache($package, $cacheKey, $result);

		return $result;
	}

	/**
	 * @return array  list of packages (cached if possible)
	 */
	public function getPackages() {
		$packages = $this->getCache('', 'packages');

		if ($packages === null || ($this->refreshed === false && sly_Core::isDeveloperMode())) {
			$packages = $this->findPackages();

			$this->refreshed = true;
			$this->setCache('', 'packages', $packages);
		}

		return $packages;
	}

	/**
	 * @return array  list of found packages
	 */
	public function findPackages() {
		$root     = $this->baseDirectory();
		$packages = array();

		// If we're in a real composer vendor directory, there is a installed.json,
		// that contains a list of all packages. We use this to detect packages
		// have no composer.json themselves (aka leafo/lessphp).
		// On the other hand, we must make sure that we only read those packages
		// that are actually inside $root, as the installed.json will contain data
		// about *all* packages (i.e. for vendors and addons)!
		$installed = $root.'composer/installed.json';

		if (file_exists($installed)) {
			$data = sly_Util_JSON::load($installed);

			foreach ($data as $pkg) {
				if (is_dir($root.$pkg['name'])) {
					$packages[] = $pkg['name'];
				}
			}

			$installed = $root.'composer/installed_dev.json';

			if (file_exists($installed)) {
				$data = sly_Util_JSON::load($installed);

				foreach ($data as $pkg) {
					if (is_dir($root.$pkg['name'])) {
						$packages[] = $pkg['name'];
					}
				}
			}
		}

		// In addition to the installed.json, we should also scan the filesystem
		// for valid packages. This makes it *much* easier to develop addOns
		// and not modify and update your composer files over and over again.

		$dirs = $this->readDir($root);

		foreach ($dirs as $dir) {
			// evil package not conforming to naming convention
			if ($this->exists($dir, true)) {
				$packages[] = $dir;
			}
			else {
				$subdirs = $this->readDir($root.$dir);

				foreach ($subdirs as $subdir) {
					// good package
					if ($this->exists($dir.'/'.$subdir, true)) {
						$packages[] = $dir.'/'.$subdir;
					}
				}
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
