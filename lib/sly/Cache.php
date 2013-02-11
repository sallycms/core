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
 * Caching wrapper
 *
 * @ingroup cache
 */
class sly_Cache extends BabelCache_Factory {
	private static $cacheImpls = array(
		'BabelCache_APC'              => 'APC',
		'BabelCache_Blackhole'        => 'Blackhole',
//		'BabelCache_Filesystem'       => 'Filesystem',
		'BabelCache_Filesystem_Plain' => 'Filesystem',
		'BabelCache_eAccelerator'     => 'eAccelerator',
		'BabelCache_Memcache'         => 'Memcache',
		'BabelCache_Memcached'        => 'Memcached',
		'BabelCache_Memory'           => 'Memory',
		'BabelCache_SQLite'           => 'SQLite',
		'BabelCache_XCache'           => 'XCache',
		'BabelCache_ZendServer'       => 'ZendServer'
	); ///< array

	public function getCache($className) {
		if ($className === 'BabelCache_Filesystem' || $className === 'BabelCache_Filesystem_Plain') {
			BabelCache_Filesystem::setDirPermissions(sly_Core::getDirPerm());
			BabelCache_Filesystem::setFilePermissions(sly_Core::getFilePerm());
		}

		return parent::getCache($className);
	}

	/**
	 * @return array  list of implementations ({className: title, className: title})
	 */
	public static function getAvailableCacheImpls() {
		$result = array();
		foreach (self::$cacheImpls as $cacheimpl => $name) {
			$available = call_user_func(array($cacheimpl, 'isAvailable'));
			if ($available) $result[$cacheimpl] = $name;
		}
		return $result;
	}

	/**
	 * @param  string $className     class name of the desired caching strategy
	 * @param  string $fallback      fallback in case $strategy is not available
	 * @return BabelCache_Interface  the caching instance to use
	 */
	public static function factory($className, $fallback = 'BabelCache_Blackhole') {
		$available = call_user_func(array($className, 'isAvailable'));

		if (!$available) {
			trigger_error('Bad caching strategy ('.$className.'), falling back to '.$fallback.'.', E_USER_WARNING);
			$className = $fallback;
		}

		$factory = new self();
		return $factory->getCache($className);
	}

	/**
	 * @see    BabelCache::generateKey()
	 * @param  mixed $vars  dummy parameter, this method can be called with as many arguments as you like
	 * @return string       a sanatized string encoding all the given arguments
	 */
	public static function generateKey($vars) {
		$vars = func_get_args();
		return call_user_func_array(array('BabelCache', 'generateKey'), $vars);
	}

	/**
	 * @return string  the cache prefix (to avoid collisions between projects using the same cache)
	 */
	protected function getPrefix() {
		return sly_Core::config()->get('INSTNAME');
	}

	/**
	 * @return string  the directory to store the filesystem cache
	 */
	protected function getCacheDirectory() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal', 'sally', 'fscache');
		return sly_Util_Directory::create($dir);
	}

	/**
	 * @return PDO
	 */
	protected function getSQLiteConnection() {
		$db = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal', 'sally', 'cache.sqlite');

		if (!file_exists($db)) {
			touch($db);
			chmod($db, sly_Core::getFilePerm());
		}

		return BabelCache_SQLite::connect($db);
	}

	/**
	 * Return memcache address
	 *
	 * This method should return the memcache server address as a single
	 * array(host, port).
	 *
	 * @return array  array(host, port)
	 */
	protected function getMemcacheAddress() {
		return sly_Core::config()->get('babelcache/memcached');
	}
}
