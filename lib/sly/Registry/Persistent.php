<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
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
	private $store;  ///< sly_Util_Array
	private $pdo;    ///< sly_DB_PDO_Persistence
	private $prefix; ///< string

	public function __construct(sly_DB_PDO_Persistence $persistence) {
		$this->store  = new sly_Util_Array();
		$this->pdo    = $persistence;
		$this->prefix = $persistence->getPrefix();
	}

	/**
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function set($key, $value) {
		$this->pdo->replace('registry', array('value' => serialize($value)), array('name' => $key));

		return $this->store->set($key, $value);
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		if ($this->has($key)) {
			return $this->store->get($key);
			// fallthrough -> Fehlerbehandlung durch sly_Util_Array
		}

		return $default;
	}

	/**
	 * @param  string $key
	 * @return boolean
	 */
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

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function remove($key) {
		$this->pdo->delete('registry', array('name' => $key));
		return $this->store->remove($key);
	}

	/**
	 * @param string $key
	 */
	public function flush($key = '*') {
		$pattern = str_replace(array('*', '?'), array('%', '_'), $key);
		$table   = $this->prefix.'registry';

		$this->pdo->query('DELETE FROM '.$table.' WHERE `name` LIKE ?', array($pattern));
		$this->store = new sly_Util_Array();
	}

	/**
	 * @param  string $key
	 * @return mixed
	 */
	protected function getValue($key) {
		return $this->pdo->magicFetch('registry', 'value', array('name' => $key));
	}
}
