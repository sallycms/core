<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Factory;
use wv\BabelCache\Adapter;

/**
 * Caching wrapper
 *
 * @ingroup cache
 */
class sly_Cache_Factory extends Factory {
	protected $config;
	protected $prefix;

	public function __construct(array $babelCacheConfig, $prefix) {
		parent::__construct();

		$this->config = $babelCacheConfig;
		$this->prefix = $prefix;
	}

	protected function constructFilesystem($className) {
		$path     = $this->getCacheDirectory();
		$instance = new $className($path, sly_Core::getFilePerm(), sly_Core::getDirPerm());

		return $instance;
	}

	/**
	 * Return memcached server addresses
	 *
	 * This method should return a list of servers, each one being a tripel of
	 * [host, port, weight].
	 *
	 * @return array  array(array(host, port, weight))
	 */
	public function getMemcachedAddresses() {
		return isset($this->config['memcached']) ? $this->config['memcached'] : null;
	}

	/**
	 * Return memcached SASL auth data
	 *
	 * This method should return a tupel, consisting of the username and the
	 * password for the memcached daemon. If this method returns null, it's
	 * assumed no auth is available/needed and the MemcachedSASL adapter is
	 * disabled.
	 *
	 * @return mixed  array(username, password) or null to disable SASL support
	 */
	public function getMemcachedAuthentication() {
		return isset($this->config['memcached_sasl']) ? $this->config['memcached_sasl'] : null;
	}

	/**
	 * Return Redis server addresses
	 *
	 * See https://github.com/nrk/predis#connecting-to-redis for more info on
	 * what shape the address list can take. Return null to disable the Redis
	 * adapter.
	 *
	 * @return array  [{host: ..., port: ...}] or null
	 */
	public function getRedisAddresses() {
		return isset($this->config['redis']) ? $this->config['redis'] : null;
	}

	/**
	 * Return ElastiCache configuration endpoint
	 *
	 * This method should return a tupel of [hostname, port].
	 *
	 * @return array  array(host, port) or null
	 */
	public function getElastiCacheEndpoint() {
		return isset($this->config['elasticache']) ? $this->config['elasticache'] : null;
	}

	/**
	 * Return caching prefix (only useful for in-memory caches)
	 *
	 * @return string  the prefix
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return string  the absolute path to the cache directory
	 */
	public function getCacheDirectory() {
		return sly_Util_Directory::create(SLY_TEMPFOLDER.'/sally/fscache');
	}

	/**
	 * @return PDO
	 */
	public function getSQLiteConnection() {
		$db = sly_Util_Directory::join(SLY_TEMPFOLDER, 'sally', 'cache.sqlite');

		if (!file_exists($db)) {
			touch($db);
			chmod($db, sly_Core::getFilePerm());
		}

		return Adapter\SQLite::connect($db);
	}

	/**
	 * @return string
	 */
	public function getSQLiteTableName() {
		return 'babelcache';
	}

	/**
	 * @return PDO
	 */
	public function getMySQLConnection() {
		if (!isset($this->config['mysql'])) {
			return null;
		}

		$mysql = $this->config['mysql'];

		return Adapter\MySQL::connect($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database']);
	}

	/**
	 * @return string
	 */
	public function getMySQLTableName() {
		if (!isset($this->config['mysql'])) {
			return null;
		}

		return $this->config['mysql']['table'];
	}
}
