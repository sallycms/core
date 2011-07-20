<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
	protected static $loadPaths       = array();
	protected static $counter         = 0;
	protected static $pathHash        = 0;
	protected static $pathCache       = array();
	protected static $enablePathCache = false;

	/**
	 * @param boolean $flag
	 */
	public static function enablePathCache($flag = true) {
		self::$enablePathCache = (boolean) $flag;
		self::$pathHash        = $flag ? md5(json_encode(self::$loadPaths)) : 0;
	}

	/**
	 * @param string $path
	 * @param string $hiddenPrefix
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
		if (function_exists('spl_autoload_register')) {
			spl_autoload_register(array('sly_Loader', 'loadClass'));
		}
		else {
			function __autoload($className) {
				self::loadClass($className);
			}
		}
	}

	/**
	 * @param  string $className
	 * @return boolean|string
	 */
	public static function loadClass($className) {
		/*if (class_exists($className, false)) {
			return true;
		}*/

		$found = false;

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

			// init classes

			switch ($className) {
				case 'sly_Log':
					sly_Log::setLogDirectory(SLY_DYNFOLDER.'/internal/sally/logs');
					break;
			}

			++self::$counter;
			return $fullPath;
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notifyUntil('__AUTOLOAD', $className);

		if (class_exists($className, false)) {
			++self::$counter;
			return true;
		}

		return false;
	}

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

			$file     = str_replace('_', DIRECTORY_SEPARATOR, $shortClass).'.php';
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
	 * @return int
	 */
	public static function getClassCount() {
		return self::$counter;
	}

	/**
	 * @return string
	 */
	public static function getCacheFile() {
		return self::getCacheDir().'/'.self::$pathHash.'.php';
	}

	/**
	 * @return string
	 */
	public static function getCacheDir() {
		static $dir = null;

		if ($dir === null) {
			$dir = SLY_DYNFOLDER.'/internal/sally/loader';

			if (is_dir(dirname($dir)) && !is_dir($dir)) {
				mkdir($dir, 0777);
				chmod($dir, 0777); // hoster compatibility
			}
		}

		return $dir;
	}

	private static function storePathCache() {
		if (is_dir(self::getCacheDir())) {
			$filename = self::getCacheFile();
			$exists   = file_exists($filename);

			file_put_contents($filename, '<?php $config = '.var_export(self::$pathCache, true).';', LOCK_EX);
			if (!$exists) chmod($filename, 0777);
		}
	}

	/**
	 * @param  array $params
	 * @return mixed
	 */
	public static function clearCache($params = array()) {
		$dir   = new sly_Util_Directory(self::getCacheDir());
		$files = $dir->listPlain(true, false);

		foreach ($files as $file) {
			unlink($dir.'/'.$file);
		}

		self::$pathCache = array();

		return isset($params['subject']) ? $params['subject'] : true;
	}
}
