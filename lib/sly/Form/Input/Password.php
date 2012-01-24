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
 * Password input
 *
 * The password input is one of the few elements that explicitly don't show the
 * submitted value. This behaviour can be toggled by calling
 * setPasswordReDisplay().
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_Password extends sly_Form_Input_Base {
	protected $redisplay; ///< boolean  whether to show the password after the form has been submitted

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
		$this->setAttribute('type', 'password');
		$this->addClass('sly-form-password');
		$this->addOuterClass('sly-form-password-row');
		$this->redisplay = false;
	}

	/**
	 * Enables or disables the password re-displaying
	 *
	 * If false (the default), the value of this element will not be shown after
	 * the form has been submitted and is displayed again (maybe because there
	 * was an error). Set this to true to enable it, but be aware that this will
	 * put the unencrypted password in the XHTML output.
	 *
	 * @param boolean $redisplay  true or false
	 */
	public function setPasswordReDisplay($redisplay) {
		$this->redisplay = (boolean) $redisplay;
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * See setPasswordReDisplay() for a description of the special behaviour of
	 * this element.
	 *
	 * @return string  (maybe) submitted password
	 */
	public function getDisplayValue() {
		if ($this->redisplay) {
			return parent::getDisplayValue();
		}

		return $this->attributes['value'];
	}
}
