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

	protected $_attributes = array('module' => 'string'); ///< array
	protected $_hasMany    = array('SliceValue' => array('delete_cascade' => true, 'foreign_key' => array('slice_id' => 'id'))); ///< array

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
	 * @param  string $type
	 * @param  string $finder
	 * @param  string $value
	 * @return sly_Model_SliceValue
	 */
	public function addValue($finder, $value = null) {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->create(array('slice_id' => $this->getId(), 'finder' => $finder, 'value' => $value));
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @return mixed
	 */
	public function getValue($finder) {
		$service    = sly_Service_Factory::getSliceValueService();
		$sliceValue = $service->findBySliceFinder($this->getId(), $finder);

		return $sliceValue ? $sliceValue->getValue() : null;
	}

	public function setValues($values = array()) {
		$sql = sly_DB_Persistence::getInstance();
		try {
			$sql->beginTransaction();
			$this->flushValues();
			foreach($values as $finder => $value) {
				$this->addValue($finder, $value);
			}
			$sql->commit();
		}catch(Exception $e) {
			$sql->rollBack();
			throw $e;
		}
	}

	public function getValues() {
		$values      = array();
		$service     = sly_Service_Factory::getSliceValueService();
		$sliceValues = $service->find(array('slice_id' => $this->getId()));
		foreach($sliceValues as $value) {
			$values[$value->getFinder()] = $value->getValue();
		}
		return $values;
	}

	/**
	 * @return int
	 */
	public function flushValues() {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->delete(array('slice_id' => $this->getId()));
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
