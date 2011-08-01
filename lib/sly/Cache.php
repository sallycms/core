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
 * @ingroup cache
 */
class sly_Cache extends BabelCache_Factory {
	private static $cachingStrategy = null;   ///< string
	private static $instance        = null;

	private static $cacheImpls = array(
		'BabelCache_APC'          => 'APC',
		'BabelCache_Blackhole'    => 'Blackhole',
		'BabelCache_Filesystem'   => 'Filesystem',
		'BabelCache_eAccelerator' => 'eAccelerator',
		'BabelCache_Memcache'     => 'Memcache',
		'BabelCache_Memcached'    => 'Memcached',
		'BabelCache_Memory'       => 'Memory',
		'BabelCache_XCache'       => 'XCache',
		'BabelCache_ZendServer'   => 'ZendServer'
	);

	/**
	 * @return array  [className: title, className: title]
	 */
	public static function getAvailableCacheImpls() {
		$result = array();
		foreach (self::$cacheImpls as $cacheimpl => $name) {
			$available = call_user_func(array($cacheimpl, 'isAvailable'));
			if ($available) $result[$cacheimpl] = $name;
		}
		return $result;
	}

	public static function disable() {
		self::getInstance()->disableCaching();
	}

	public static function enable() {
		self::getInstance()->enableCaching();
	}

	/**
	 * @return string
	 */
	public static function getStrategy() {
		return sly_Core::getCachingStrategy();
	}

	/**
	 * @return string
	 */
	public static function getFallbackStrategy() {
		return sly_Core::config()->get('FALLBACK_CACHING_STRATEGY', 'sly_Cache_Blackhole');
	}

	/**
	 * @param  string $forceCache
	 * @return BabelCache_Interface
	 */
	public static function factory($forceCache = null) {
		if (self::$cachingStrategy === null) {
			self::$cachingStrategy = self::getStrategy();
		}

		if (SLY_IS_TESTING) {
			$forceCache = 'BabelCache_Blackhole';
		}

		if ($forceCache !== null) {
			$cachingStrategy = $forceCache;
		}
		else {
			$cachingStrategy = self::$cachingStrategy;
		}

		// Prüfen, ob der Cache verfügbar ist

		$available = call_user_func(array($cachingStrategy, 'isAvailable'));

		if (!$available) {
			$fallback = self::getFallbackStrategy();
			// Warnung auslösen, um jemanden auf das Problem aufmerksam zu machen
			trigger_error('Bad caching strategy. Falling back to '.$fallback, E_USER_WARNING);
			$cachingStrategy = $fallback;
		}

		// Wir merken uns den aktuell gewählten Cache.

		if ($forceCache === null) {
			self::$cachingStrategy = $cachingStrategy;
		}

		if ($cachingStrategy === 'BabelCache_Filesystem') {
			BabelCache_Filesystem::setDirPermissions(sly_Core::getDirPerm());
			BabelCache_Filesystem::setFilePermissions(sly_Core::getFilePerm());
		}

		return self::getInstance()->getCache($cachingStrategy);
	}

	private function __construct() {
	}

	/**
	 * @see BabelCache::generateKey()
	 */
	public static function generateKey($vars) {
		$vars = func_get_args();
		return call_user_func_array(array('BabelCache', 'generateKey'), $vars);
	}

	/**
	 * @return sly_Cache
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	protected function getPrefix() {
		return sly_Core::config()->get('INSTNAME');
	}

	protected function getCacheDirectory() {
		return sly_Util_Directory::join(SLY_DYNFOLDER, 'internal', 'sally', 'fscache');
	}
}
