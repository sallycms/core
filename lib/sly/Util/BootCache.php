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

	/**
	 * @param string $environment
	 */
	public static function init($environment) {
		// add core classes
		$list = sly_Util_YAML::load(SLY_COREFOLDER.'/config/bootcache.yml');
		self::$classes = $list['static'];

		if (isset($list[$environment])) {
			self::$classes = array_merge(self::$classes, $list[$environment]);
		}

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

	/**
	 * @param string $environment
	 */
	public static function recreate($environment) {
		// when in developer mode, only remove a possibly existing cache file

		if (sly_Core::isDeveloperMode()) {
			$target = self::getCacheFile($environment);

			if (file_exists($target)) {
				unlink($target);
			}

			return;
		}

		// create the file

		self::init($environment);
		sly_Core::dispatcher()->notify('SLY_BOOTCACHE_CLASSES_'.strtoupper($environment));
		self::createCacheFile($environment);
	}

	/**
	 * @param string $className
	 */
	public static function addClass($className) {
		self::$classes[] = $className;
		self::$classes   = array_unique(self::$classes);
	}

	/**
	 * @param  string $environment
	 * @return string
	 */
	public static function getCacheFile($environment) {
		return SLY_DYNFOLDER.'/internal/sally/bootcache.'.$environment.'.php';
	}

	/**
	 * @param string $environment
	 */
	public static function createCacheFile($environment) {
		$target = self::getCacheFile($environment);

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

		// add functions

		$functionFiles = array(
			'lib/compatibility.php',
			'lib/functions.php'
		);

		foreach ($functionFiles as $fctFile) {
			$code = self::getCode(SLY_COREFOLDER.'/'.$fctFile);
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

		// remove comments and collapse whitespace into single spaces

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

						case T_WHITESPACE:
							$result .= ' ';
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
