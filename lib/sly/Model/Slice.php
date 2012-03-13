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
 * Business Model for Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
class sly_Model_Slice extends sly_Model_Base_Id {
	protected $module; ///< string
	protected $values; ///< array

	protected $_attributes = array('module' => 'string', 'values' => 'array'); ///< array

	public function __construct($params = array()) {
		if (!is_array($params['values'])) $params['values'] = json_decode($params['values'], true);
		if ($params['values'] === null) $params['values'] = array();
		parent::__construct($params);
	}

	public function toHash() {
		$data = array();
		foreach ($this->_attributes as $name => $type) {
			if($name === 'values') {
				$data[$name] = json_encode($this->$name);
			} else {
				$data[$name] = $this->$name;
			}
		}
		return $data;
	}

		/**
	 * @return string
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @param string $module
	 */
	public function setModule($module) {
		$this->module = $module;
	}

	/**
	 * @param  string $finder
	 * @param  string $value
	 */
	public function setValue($finder, $value = null) {
		$this->values[$finder] = $value;
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @return mixed
	 */
	public function getValue($finder, $default = null) {
		return isset($this->values[$finder]) ? $this->values[$finder] : $default;
	}

	public function setValues($values = array()) {
		if(!sly_Util_Array::isAssoc($values)) {
			throw new sly_Exception('Values must be assoc array!');
		}
		$this->values = sly_makeArray($values);
	}

	public function getValues() {
		return $this->values;
	}

	/**
	 * get the rendered output
	 *
	 * @return string
	 */
	public function getOutput() {
		$values   = $this->getValues();
		$renderer = new sly_Slice_Renderer($this->getModule(), $values);
		$output   = $renderer->renderOutput($this);
		return $output;
	}

}
