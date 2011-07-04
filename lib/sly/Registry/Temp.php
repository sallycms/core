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
class sly_Registry_Temp implements sly_Registry_Registry {

	private static $instance;

	private $store;

	private function __construct() {
		$this->store = new sly_Util_Array();
	}

	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	public function set($key, $value) {
		return $this->store->set($key, $value);
	}

	public function get($key) {
		return $this->store->get($key);
	}

	public function has($key) {
		return $this->store->has($key);
	}

	public function remove($key) {
		return $this->store->remove($key);
	}
}
