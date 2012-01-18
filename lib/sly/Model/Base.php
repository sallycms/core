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
 * Basisklasse für alle Models
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
abstract class sly_Model_Base {
	protected $_pk;         ///< array
	protected $_attributes; ///< array
	protected $_values;     ///< array

	/**
	 * @param array $params
	 */
	public function __construct($params = array()) {
		foreach ($this->_pk as $name => $type) {
			if (isset($params[$name])) {
				$this->$name = $params[$name];
				settype($this->$name, $type);
			}
		}

		foreach ($this->_attributes as $name => $type) {
			if (isset($params[$name])) {
				$this->$name = $params[$name];
				settype($this->$name, $type);
			}
		}

		// put left over values in $_values to allow access from __call

		$hangover = array_diff(array_keys($params), array_keys($this->_pk), array_keys($this->_attributes));

		foreach ($hangover as $key) {
			$this->_values[$key] = $params[$key];
		}
	}

	/**
	 * @return array
	 */
	public function toHash() {
		return $this->attrsToHash($this->_attributes);
	}

	/**
	 * @return array
	 */
	public function getPKHash() {
		return $this->attrsToHash($this->_pk);
	}

	/**
	 * @param sly_Model_User $user
	 */
	public function setUpdateColumns($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setUpdateDate(time());
		$this->setUpdateUser($user);
	}

	/**
	 * @param sly_Model_User $user
	 */
	public function setCreateColumns($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setCreateDate(time());
		$this->setCreateUser($user);
		$this->setUpdateColumns($user);
	}

	/**
	 * @param  array $attrs
	 * @return array
	 */
	protected function attrsToHash($attrs) {
		$data = array();

		foreach ($attrs as $name => $type) {
			$data[$name] = $this->$name;
		}

		return $data;
	}

	/**
	 * @return array
	 */
	public function getDeleteCascades() {
		$cascade = array();
		if (!isset($this->_hasMany))
			return $cascade;

		foreach ($this->_hasMany as $model => $config) {
			if (isset($config['delete_cascade']) && $config['delete_cascade'] === true) {
				$cascade[$model] = $this->getForeignKeyForHasMany($model);
			}
		}

		return $cascade;
	}

	/**
	 * @param  string $model
	 * @return array
	 */
	private function getForeignKeyForHasMany($model) {
		$fk = $this->_hasMany[$model]['foreign_key'];

		foreach ($fk as $column => $value) {
			$fk[$column] = $this->$value;
		}

		return $fk;
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function getExtendedValue($key, $default = null) {
		return isset($this->_values[$key]) ? $this->_values[$key] : $default;
	}

	/**
	 * @throws sly_Exception
	 * @param  string $method
	 * @param  array  $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		if (isset($this->_hasMany) && is_array($this->_hasMany)) {
			foreach ($this->_hasMany as $model => $config) {
				if ($method == 'get'.$model.'s') {
					return sly_Service_Factory::getService($model)->find($this->getForeignKeyForHasMany($model));
				}
			}
		}

		$event      = strtoupper(get_class($this).'_'.$method);
		$dispatcher = sly_Core::dispatcher();

		if (!$dispatcher->hasListeners($event)) {
			throw new sly_Exception('Call to undefined function '.$method.'()');
		}

		return $dispatcher->filter($event, null, array('method' => $method, 'arguments' => $arguments, 'object' => $this));
	}
}
