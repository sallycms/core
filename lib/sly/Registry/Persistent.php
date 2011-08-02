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
 * @ingroup registry
 */
class sly_Registry_Persistent implements sly_Registry_Registry {

	private static $instance;
	private $store;
	private $pdo;
	private $prefix;

	private function __construct() {
		$this->store  = new sly_Util_Array();
		$this->pdo    = sly_DB_Persistence::getInstance();
		$this->prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
	}

	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	public function set($key, $value) {
		$qry = 'REPLACE INTO '.$this->prefix.'registry (`name`, `value`) VALUES (?,?)';
		$this->pdo->query($qry, array($key, serialize($value)));

		return $this->store->set($key, $value);
	}

	public function get($key, $default = null) {
		if ($this->has($key)) {
			return $this->store->get($key);
			// fallthrough -> Fehlerbehandlung durch sly_Util_Array
		}

		return $default;
	}

	public function has($key) {
		if ($this->store->has($key)) return true;

		$value = $this->getValue($key);

		if ($value !== false) {
			$value = unserialize($value);
			$this->store->set($key, $value);
			return true;
		}

		return false;
	}

	public function remove($key) {
		$this->pdo->delete('registry', array('name' => $key));
		return $this->store->remove($key);
	}

	public function flush($key = '*') {
		$pattern = str_replace(array('*', '?'), array('%', '_'), $key);
		$table   = $this->prefix.'registry';

		$this->pdo->query('DELETE FROM '.$table.' WHERE `name` LIKE ?', array($pattern));
		$this->store = new sly_Util_Array();
	}

	protected function getValue($key) {
		return $this->pdo->magicFetch('registry', 'value', array('name' => $key));
	}
}
