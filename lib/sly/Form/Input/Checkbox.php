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
 * Single checkbox
 *
 * This class wraps a single checkbox element with a description. If you need
 * to toggle a list of elements, use sly_Form_Select_Checkbox. This one is used
 * for boolean like values.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_Checkbox extends sly_Form_Input_Boolean {
	/**
	 * Constructor
	 *
	 * @param string $name         element name
	 * @param string $label        the label
	 * @param array  $value        the current text
	 * @param string $description  text to show right next to the checkbox
	 * @param string $id           optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value, $description = 'ja', $id = null) {
		parent::__construct($name, $label, $value, $description, $id);

		$this->setAttribute('type', 'checkbox');
		$this->addOuterClass('sly-form-checkbox');
		$this->addClass('sly-form-checkbox');
	}
}
