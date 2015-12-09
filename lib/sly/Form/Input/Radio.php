<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Single radiobox
 *
 * This class wraps a single radio element with a description. In most cases,
 * you will use the list based element sly_Form_Select_Radio, since selecting
 * when having only a single radio button is pretty useless. But it can be
 * useful when enhancing a group of elements with JavaScript.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_Radio extends sly_Form_Input_Boolean {
	/**
	 * Constructor
	 *
	 * @param string $name         element name
	 * @param string $label        the label
	 * @param array  $value        the current text
	 * @param string $description  text to show right next to the checkbox
	 * @param string $id           optional ID (if not given, the name is used)
	 * @param string $checked      toggles the checked attribute
	 */
	public function __construct($name, $label, $value, $description = 'ja', $id = null, $checked = false) {
		parent::__construct($name, $label, $value, $description, $id, $checked);

		$this->setAttribute('type', 'radio');
		$this->addOuterClass('sly-form-radio-row');
		$this->addClass('sly-form-radio');
	}
}
