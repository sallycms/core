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
 * @ingroup util
 */
class sly_Util_BootCache {
	protected static $classes = array();

	public static function init() {
		// add core classes
		self::$classes = sly_Util_YAML::load(SLY_COREFOLDER.'/config/bootcache.yml');

		// add current cache instance
		self::addClass(get_class(sly_Core::cache()));
	}

	public static function recreate() {
		// when in developer mode, only remove a possibly existing cache file

		if (sly_Core::isDeveloperMode()) {
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

	public static function addClass($className) {
		self::$classes[] = $className;
		self::$classes   = array_unique(self::$classes);
	}

	public static function getCacheFile() {
		return SLY_DYNFOLDER.'/internal/sally/bootcache.php';
	}

	public static function createCacheFile() {
		$target = self::getCacheFile();

		if (file_exists($target)) {
			unlink($target);
		}

		foreach (self::$classes as $class) {
			$filename = sly_Loader::findClass($class);
			if (!$filename) continue;

			$code = file_get_contents($filename);
			$code = trim($code);

			file_put_contents($target, $code."\n\n?>", FILE_APPEND);
		}

		// add functions

		$functionFiles = array(
			'lib/compatibility.php',
			'lib/functions.php',
			'functions/function_rex_globals.inc.php',
			'functions/function_rex_client_cache.inc.php',
			'functions/function_rex_other.inc.php',
			'functions/function_rex_generate.inc.php'
		);

		foreach ($functionFiles as $fctFile) {
			$code = file_get_contents(SLY_COREFOLDER.'/'.$fctFile);
			$code = trim($code);

			file_put_contents($target, $code."\n\n?>", FILE_APPEND);
		}
	}
}
