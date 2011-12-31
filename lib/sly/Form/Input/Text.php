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
 * Normal text input
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_Text extends sly_Form_Input_Base {
	/**
	 * Constructor
	 *
	 * @param string $name    element name
	 * @param string $label   the label
	 * @param array  $value   the current text
	 * @param string $id      optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value = '', $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('type', 'text');
	}

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass() {
		$this->addOuterClass('rex-form-text');
		return $this->outerClass;
	}
}
