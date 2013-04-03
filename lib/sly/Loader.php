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
 * @ingroup core
 */
class sly_Loader {
	protected static $loadPaths       = array();  ///< array
	protected static $pathHash        = 0;        ///< int
	protected static $pathCache       = array();  ///< array
	protected static $enablePathCache = false;    ///< boolean

	/**
	 * @param boolean $flag  whether to use the path cache or not
	 */
	public static function enablePathCache($flag = true) {
		self::$enablePathCache = (boolean) $flag;
		self::$pathHash        = $flag ? md5(json_encode(self::$loadPaths)) : 0;
	}

	/**
	 * @param string $path          new absolute directory path to a class collection
	 * @param string $hiddenPrefix  a prefix to remove from class names before matching this directory
	 */
	public static function addLoadPath($path, $hiddenPrefix = '') {
		$path = realpath($path);

		if ($path) {
			$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			self::$loadPaths[$path] = $hiddenPrefix;

			if (self::$enablePathCache) {
				self::$pathHash = md5(json_encode(self::$loadPaths));

				// import path cache

				$filename = self::getCacheFile();

				if (file_exists($filename)) {
					// lock the file
					$handle = fopen($filename, 'r');
					flock($handle, LOCK_SH);

					include $filename;
					self::$pathCache = isset($config) ? $config : array();

					// release lock again
					flock($handle, LOCK_UN);
					fclose($handle);
				}
				else {
					self::$pathCache = array();
				}
			}
		}
	}

	public static function register() {
		spl_autoload_register(array('sly_Loader', 'loadClass'));
	}

	/**
	 * @param  string $className  class to load
	 * @return boolean            true if the class was loaded, else false
	 */
	public static function loadClass($className) {
		$found = false;

		if (strlen($className) === 0) {
			throw new sly_Exception('No class name given.');
		}

		// remove leading \
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}

		if (isset(self::$pathCache[$className])) {
			$fullPath = self::$pathCache[$className];
			$found    = true;
		}
		else {
			$fullPath = self::findClass($className);
			$found    = (boolean) $fullPath;

			if ($fullPath && self::$enablePathCache) {
				// update path cache file
				self::$pathCache[$className] = realpath($fullPath);
				self::storePathCache();
			}
		}

		if ($found) {
			include_once $fullPath;
			return true;
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notifyUntil('__AUTOLOAD', $className);

		return class_exists($className, false);
	}

	/**
	 * @param  string $className  class to find
	 * @return mixed              the full path to the class file (string) or false
	 */
	public static function findClass($className) {
		$upper = strtoupper($className);

		foreach (self::$loadPaths as $path => $prefix) {
			// Präfix vom Klassennamen abschneiden, wenn Klasse damit beginnt.

			if (!empty($prefix) && strpos($upper, strtoupper($prefix)) === 0) {
				$shortClass = substr($className, strlen($prefix));
			}
			else {
				$shortClass = $className;
			}

			if (strpos($className, '\\') !== false) {
				$file = str_replace('\\', DIRECTORY_SEPARATOR, $shortClass).'.php';
			}
			else {
				$file = str_replace('_', DIRECTORY_SEPARATOR, $shortClass).'.php';

				// allow class names to be prefixed with a single underscore

				if ($file[0] === DIRECTORY_SEPARATOR) {
					$file[0] = '_';
				}
			}

			$fullPath = $path.DIRECTORY_SEPARATOR.$file;

			// file_exists + !is_dir is faster than calling is_file and since we
			// do not care whether the file really has a class in it, we can skip
			// this check.

			if (file_exists($fullPath) && !is_dir($fullPath)) {
				return $fullPath;
			}
		}

		return false;
	}

	/**
	 * @return string  path to the current cache file
	 */
	public static function getCacheFile() {
		return self::getCacheDir().'/'.self::$pathHash.'.php';
	}

	/**
	 * @return string  path to cache directory
	 */
	public static function getCacheDir() {
		static $dir = null;

		if ($dir === null) {
			$dir  = SLY_TEMPFOLDER.'/sally/loader';
			$perm = 0777; // hard-coded since sly_Core may not have been loaded yet

			if (is_dir(dirname($dir)) && !is_dir($dir)) {
				mkdir($dir, $perm);
				chmod($dir, $perm);
			}
		}

		return $dir;
	}

	private static function storePathCache() {
		if (is_dir(self::getCacheDir())) {
			$filename = self::getCacheFile();
			$exists   = file_exists($filename);

			file_put_contents($filename, '<?php $config = '.var_export(self::$pathCache, true).';', LOCK_EX);
			if (!$exists) chmod($filename, 0777); // hard-coded since sly_Core may not have been loaded yet
		}
	}

	/**
	 * @param  array $params  event parameters
	 * @return mixed          the event's subject
	 */
	public static function clearCache($params = array()) {
		$dir   = new sly_Util_Directory(self::getCacheDir());
		$files = $dir->listPlain(true, false);

		if ($files) {
			foreach ($files as $file) {
				unlink($dir.'/'.$file);
			}
		}

		self::$pathCache = array();

		return isset($params['subject']) ? $params['subject'] : true;
	}
}
