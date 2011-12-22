<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_SliceRenderer {

	private $module;
	private $values;

	/**
	 * @param array $values
	 */
	public function __construct($module, $values = array()) {
		$this->module = $module;
		$this->setValues($values);
	}

	/**
	 * sets the values that can be displayed
	 *
	 * @param array $values
	 */
	public function setValues($values) {
		if(!sly_Util_Array::isAssoc($values)) {
			throw new sly_Exception('Values must be assoc array!');
		}
		$this->values = $values;
	}

	protected function value($id, $default) {
		if(!array_key_exists($id, $this->values)) return $default;
		return $this->values[$id];
	}

	public function renderInput() {
		$service                      = sly_Service_Factory::getModuleService();
		$filenameHtuG50hNCdikAvf7CZ1F = $service->getFolder().DIRECTORY_SEPARATOR.$service->getInputFilename($this->module);

		$form = new sly_Form('', '', $service->getTitle($this->module));
		unset($service);
		ob_start();
		include $filenameHtuG50hNCdikAvf7CZ1F;
		print $form->render(true);
		return ob_get_clean();
	}

	public function renderOutput() {
		$service                      = sly_Service_Factory::getModuleService();
		$filenameHtuG50hNCdikAvf7CZ1F = $service->getFolder().DIRECTORY_SEPARATOR.$service->getOutputFilename($this->module);
		unset($service);
		ob_start();
		include $filenameHtuG50hNCdikAvf7CZ1F;
		return ob_get_clean();
	}

}
