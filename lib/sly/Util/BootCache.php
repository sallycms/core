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
 * @ingroup util
 */
class sly_Util_BootCache {
	protected static $classes = array(); ///< array

	public static function init() {
		// add core classes
		$list = sly_Util_YAML::load(SLY_COREFOLDER.'/config/bootcache.yml');
		self::$classes = $list;

		// add current cache instance
		$cacheClass = get_class(sly_Core::cache());

		// add current database driver
		$driver = sly_Core::config()->get('DATABASE/DRIVER');
		$driver = strtoupper($driver);

		self::addClass('sly_DB_PDO_Driver_'.$driver);
		self::addClass('sly_DB_PDO_SQLBuilder_'.$driver);

		// TODO: Remove these dependency hacks with a more elegant solution (Reflection?)
		if ($cacheClass === 'BabelCache_Memcached') {
			self::addClass('BabelCache_Memcache');
		}

		if ($cacheClass === 'BabelCache_Filesystem_Plain') {
			self::addClass('BabelCache_Filesystem');
		}

		self::addClass($cacheClass);
	}

	public static function recreate() {
		// when in developer mode, only remove a possibly existing cache file

		if (sly_Core::isDeveloperMode() || !sly_Core::config()->get('bootcache')) {
			$target = self::getCacheFile();

			if (file_exists($target)) {
				unlink($target);
			}

			return;
		}

		// create the file

		self::init();
		sly_Core::dispatcher()->notify('SLY_BOOTCACHE_CLASSES');
		self::createCacheFile();
	}

	/**
	 * @param string $className
	 */
	public static function addClass($className) {
		self::$classes[] = $className;
		self::$classes   = array_unique(self::$classes);
	}

	/**
	 * @return string
	 */
	public static function getCacheFile() {
		return SLY_DYNFOLDER.'/internal/sally/bootcache.php';
	}

	public static function createCacheFile() {
		$target = self::getCacheFile();

		if (file_exists($target)) {
			unlink($target);
		}

		file_put_contents($target, "<?php\n");

		foreach (self::$classes as $class) {
			$filename = sly_Loader::findClass($class);
			if (!$filename) continue;

			$code = self::getCode($filename);
			file_put_contents($target, $code."\n", FILE_APPEND);
		}

	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	private static function getCode($filename) {
		$code   = file_get_contents($filename);
		$result = trim($code);

		// remove comments

		if (function_exists('token_get_all')) {
			$tokens = token_get_all($code);
			$result = '';

			foreach ($tokens as $token) {
				if (is_string($token)) {
					$result .= $token;
				}
				else {
					list($id, $text) = $token;

					switch ($id) {
						case T_COMMENT:
						case T_DOC_COMMENT:
							break;

						default:
							$result .= $text;
							break;
					}
				}
			}
		}

		// remove starting php tag
		$result = preg_replace('#^<\?(php)?#is', '', $result);

		return trim($result);
	}
}
