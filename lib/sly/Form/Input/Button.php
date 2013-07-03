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
 * Button element
 *
 * This class wraps all kinds of buttons: button, reset and submit.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_Button extends sly_Form_Input_Base {
	/**
	 * Constructor
	 *
	 * @param string $type   button, reset or submit (default: button)
	 * @param string $name   element name
	 * @param array  $value  the current text
	 */
	public function __construct($type, $name, $value) {
		parent::__construct($name, '', $value);
		$this->setAttribute('type', in_array($type, array('button', 'reset', 'submit')) ? $type : 'button');
		$this->addOuterClass('sly-form-button-row');
		$this->attributes['class'] = 'sly-button';
	}

	/**
	 * Renders the element
	 *
	 * This method renders the element.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />';
	}
}
