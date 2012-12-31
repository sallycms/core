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
 * HTML5 input field for email addresses
 *
 * @ingroup form
 * @author  Christoph
 * @since   0.5
 */
class sly_Form_Input_Email extends sly_Form_Input_Text {
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
		$this->setAttribute('type', 'email');
		$this->addClass('sly-form-email');
		$this->addOuterClass('sly-form-email-row');
	}
}
