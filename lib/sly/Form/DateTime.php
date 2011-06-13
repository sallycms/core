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
 * Date picker with timing functionality
 *
 * Implements a date picker by using jQuery UI. The widget can optionally be
 * extended with a time picker. If possible, it tries to use the native HTML5
 * date picker (date or datetime input type), currently only supported in Opera.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_DateTime extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $withTime; ///< boolean  toggles the time picker addon

	/**
	 * Constructor
	 *
	 * @param string  $name      the element's name
	 * @param string  $label     the label
	 * @param mixed   $value     the value (timestamp or null)
	 * @param string  $id        optional ID (if it should differ from $name)
	 * @param boolean $withTime  true to include the time picker, else false
	 */
	public function __construct($name, $label, $value, $id = null, $withTime = true) {
		parent::__construct($name, $label, $value, $id);

		$this->withTime   = (boolean) $withTime;
		$this->outerClass = 'rex-form-text';
	}

	/**
	 * Returns whether the time picker is enabled
	 *
	 * @return boolean  true or false
	 */
	public function withTime() {
		return $this->withTime;
	}

	/**
	 * Enables or disables the time picker
	 *
	 * @param boolean $withTime  true or false
	 */
	public function setWithTime($withTime = true) {
		$this->withTime = (boolean) $withTime;
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('form/datetime.phtml');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return string  submitted datetime value
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}
}
