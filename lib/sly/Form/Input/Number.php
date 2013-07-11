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
 * HTML5 input field for numbers (with spinbox)
 *
 * This element will be rendered as a spinbox by browsers with HTML5 support,
 * otherwise as a smaller input field. By default, the spinner will not be
 * retricted (no min/max values).
 *
 * @ingroup form
 * @author  Christoph
 * @since   0.5
 */
class sly_Form_Input_Number extends sly_Form_Input_Base {
	protected $min  = null; ///< int
	protected $max  = null; ///< int
	protected $step = 1;    ///< int

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
		$this->setAttribute('type', 'number');
		$this->addClass('sly-form-number');
		$this->addOuterClass('sly-form-number-row');
	}

	/**
	 * Sets the minimum value
	 *
	 * @param  int $min               the new value, null to reset
	 * @return sly_Form_Input_Number  the object itself
	 */
	public function setMin($min) {
		$this->min = $min === null ? null : (int) $min;
		return $this;
	}

	/**
	 * Sets the maximum value
	 *
	 * @param  int $max               the new value, null to reset
	 * @return sly_Form_Input_Number  the object itself
	 */
	public function setMax($max) {
		$this->max = $max === null ? null : (int) $max;
		return $this;
	}

	/**
	 * Sets the step value
	 *
	 * @param  int $step              the new value
	 * @return sly_Form_Input_Number  the object itself
	 */
	public function setStep($step) {
		$this->step = (int) $step;
		return $this;
	}

	/**
	 * Convenience wrapper to set the bounds
	 *
	 * This method will call setMin(), setMax() and setStep() in succession.
	 *
	 * @param  int $min               the new min value
	 * @param  int $max               the new max value
	 * @param  int $step              the new step value
	 * @return sly_Form_Input_Number  the object itself
	 */
	public function setBounds($min, $max, $step = 1) {
		$this->setMin($min);
		$this->setMax($max);
		$this->setStep($step);

		return $this;
	}

	/**
	 * Renders the element
	 *
	 * This method renders the element.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$this->attributes['min']  = $this->min;
		$this->attributes['max']  = $this->max;
		$this->attributes['step'] = $this->step;

		return parent::render();
	}
}
