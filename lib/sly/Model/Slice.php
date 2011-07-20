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
 * Business Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 *
 * @method getSliceValues()
 *
 * @ingroup model
 */
class sly_Model_Slice extends sly_Model_Base_Id {
	protected $module;
	protected $_attributes = array('module' => 'string');
	protected $_hasMany    = array('SliceValue' => array('delete_cascade' => true, 'foreign_key' => array('slice_id' => 'id')));

	public function getModule()        { return $this->module; }
	public function setModule($module) { $this->module = $module; }

	public function addValue($type, $finder, $value = null) {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->create(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder, 'value' => $value));
	}

	/**
	 *
	 * @param string $type
	 * @param string $finder
	 *
	 * @return Model_SliceValue
	 */
	public function getValue($type, $finder) {
		$service    = sly_Service_Factory::getSliceValueService();
		$sliceValue = $service->findBySliceTypeFinder(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder));
		return $sliceValue;
	}

	public function flushValues() {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->delete(array('slice_id' => $this->getId()));
	}

	public function getOutput() {
		$service  = sly_Service_Factory::getModuleService();
		$filename = $service->getOutputFilename($this->getModule());
		$output   = $service->getContent($filename);

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$output = $var->getOutput($this->getId(), $output);
		}

		return $output;
	}

	public function getInput() {
		$service  = sly_Service_Factory::getModuleService();
		$filename = $service->getInputFilename($this->getModule());
		$output   = $service->getContent($filename);

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$output = $var->getBEInput($this->getId(), $output);
		}

		return $output;
	}
}
