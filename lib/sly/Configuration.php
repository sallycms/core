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
 * System Configuration
 *
 * @ingroup core
 */
class sly_Configuration implements sly_ContainerAwareInterface{
	protected $container;    ///< sly_Container
	protected $staticStore;  ///< sly_Util_Array
	protected $dynamicStore; ///< sly_Util_Array
	protected $cache;        ///< sly_Util_Array

	/**
	 * Create a new instance. Having more than one instance with
	 * flush enabled can be very dangerous. Take care.
	 *
	 * @param sly_Service_File_Base $fileService
	 */
	public function __construct() {
		$this->cache        = null;
		$this->dynamicStore = new sly_Util_Array();
		$this->staticStore  = new sly_Util_Array();
	}

	/**
	 *
	 * @param sly_Container $container
	 */
	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	/**
	 * Store the dynamic art of the configuration
	 *
	 * @param  sly_configuration_Writer $writer
	 * @return sly_Configuration
	 */
	public function store(sly_configuration_Writer $writer = null) {
		if ($writer === null) {
			$writer = $this->container['sly-config-reader'];
		}

		$writer->write($this->dynamicStore);

		return $this;
	}

	/**
	 * @param  string $key      the key to load
	 * @param  mixed  $default  value to return when $key was not found
	 * @return mixed            the found value or $default
	 */
	public function get($key, $default = null) {
		$this->warmUp();
		return $this->cache->get($key, $default);
	}

	/**
	 * @param  string $key  the key to check
	 * @return boolean      true if found, else false
	 */
	public function has($key) {
		$this->warmUp();
		return $this->cache->has($key);
	}

	/**
	 * @param string $key  the key to remove
	 * @return sly_Configuration
	 */
	public function remove($key) {
		$this->dynamicStore->remove($key);
		$this->cache = null;
		return $this;
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return sly_Configuration
	 */
	public function setStatic($key, $value) {
		if ($key !== '/' && $this->dynamicStore->has($key)) {
			throw new sly_Exception('Keys that are in the dynamicStore can not be overwritten here!');
		}
		$this->setInternal($key, $value, $this->staticStore);
		return $this;
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return sly_Configuration
	 */
	public function set($key, $value) {
		$this->setInternal($key, $value, $this->dynamicStore);
		return $this;
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  int     $mode   one of the classes MODE constants
	 * @param  boolean $force  force reloading the config or not
	 * @return boolean         false when an error occured, true if everything went fine
	 */
	protected function setInternal($key, $value, sly_Util_Array $store) {
		if (is_null($key) || strlen($key) === 0) {
			throw new sly_Exception('Empty key is not allowed!');
		}

		if (!empty($value) && sly_Util_Array::isAssoc($value)) {
			$key = trim($key, '/');

			foreach ($value as $ikey => $val) {
				$currentPath = $key.'/'.$ikey;
				$this->setInternal($currentPath, $val, $store);
			}

			return $this;
		}

		$store->set($key, $value);
		$this->cache = null;

		return $this;
	}

	/**
	 * warm up the internal cache for get/has operations
	 */
	protected function warmUp() {
		if ($this->cache === null) {
			// build merged config cache
			$cache = array_replace_recursive($this->staticStore->get('/', array()), $this->dynamicStore->get('/', array()));
			$this->cache = new sly_Util_Array($cache);
		}
	}
}
