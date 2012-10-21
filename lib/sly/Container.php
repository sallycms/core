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
 * @ingroup core
 */
class sly_Container implements ArrayAccess, Countable {
	private $values;

	/**
	 * Constructor
	 *
	 * @param array $values  initial values
	 */
	public function __construct(array $values = array()) {
		$this->values = $values;
	}

	/**
	 * Returns the number of elements
	 *
	 * @return int
	 */
	public function count() {
		return count($this->values);
	}

	/**
	 * @param  string $id
	 * @param  mixed  $value
	 * @return sly_Container  reference to self
	 */
	public function set($id, $value) {
		$this->values[$id] = $value;
		return $this;
	}

	/**
	 * @param  string $id
	 * @return boolean
	 */
	public function has($id) {
		return array_key_exists($id, $this->values);
	}

	/**
	 * @param  string $id
	 * @return sly_Container  reference to self
	 */
	public function remove($id) {
		unset($this->values[$id]);
		return $this;
	}

	/**
	 * @throws InvalidArgumentException if the identifier is not defined
	 * @param  string $id
	 * @return mixed
	 */
	public function get($id) {
		if (!array_key_exists($id, $this->values)) {
			throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
		}

		$closures = class_exists('Closure', false);
		$value    = $this->values[$id];

		// PHP 5.3+
		if ($closures && $value instanceof Closure) {
			return $value($this);
		}

		if (is_callable($value)) {
			return call_user_func_array($value, array($this));
		}

		return $value;
	}

	/**
	 * @return sly_Configuration
	 */
	public function getConfig() {
		return $this['sly-config'];
	}

	/**
	 * @return sly_Event_Dispatcher
	 */
	public function getDispatcher() {
		return $this['sly-dispatcher'];
	}

	/**
	 * @return sly_Layout
	 */
	public function getLayout() {
		return $this['sly-layout'];
	}

	/**
	 * @return sly_I18N
	 */
	public function getI18N() {
		return $this['sly-i18n'];
	}

	/**
	 * @return sly_Registry_Temp
	 */
	public function getTempRegistry() {
		return $this['sly-registry-temp'];
	}

	/**
	 * @return sly_Registry_Persistent
	 */
	public function getPersistentRegistry() {
		return $this['sly-registry-persistent'];
	}

	/**
	 * @return sly_ErrorHandler_Interface
	 */
	public function getErrorHandler() {
		return $this['sly-error-handler'];
	}

	/**
	 * @return sly_Request
	 */
	public function getRequest() {
		return $this['sly-request'];
	}

	/**
	 * @return sly_Response
	 */
	public function getResponse() {
		return $this['sly-response'];
	}

	/**
	 * @return sly_Session
	 */
	public function getSession() {
		return $this['sly-session'];
	}

	/**
	 * @return sly_DB_PDO_Persistence
	 */
	public function getPersistence() {
		return $this['sly-persistence'];
	}

	/**
	 * @return BabelCache_Interface
	 */
	public function getCache() {
		return $this['sly-cache'];
	}

	/**
	 * @return sly_App_Interface
	 */
	public function getApplication() {
		return $this['sly-app'];
	}

	/**
	 * @param string $id
	 * @param mixed  $value
	 */
	public function offsetSet($id, $value) {
		return $this->set($id, $value);
	}

	/**
	 * @param  string $id
	 * @return boolean
	 */
	public function offsetExists($id) {
		return $this->has($id);
	}

	/**
	 * @param string $id
	 */
	public function offsetUnset($id) {
		return $this->remove($id);
	}

	/**
	 * @throws InvalidArgumentException if the identifier is not defined
	 * @param  string $id
	 * @return mixed
	 */
	public function offsetGet($id) {
		return $this->get($id);
	}
}
