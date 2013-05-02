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
 * @ingroup registry
 */
class sly_Registry_Temp implements sly_Registry_Registry {
	private $store; ///< sly_Util_Array

	public function __construct() {
		$this->store = new sly_Util_Array();
	}

	/**
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function set($key, $value) {
		return $this->store->set($key, $value);
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->store->get($key, $default);
	}

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function has($key) {
		return $this->store->has($key);
	}

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function remove($key) {
		return $this->store->remove($key);
	}
}
