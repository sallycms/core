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
 * HTML5 input field for numbers (with spinbox)
 *
 * @ingroup form
 * @author  Christoph
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
	}

	public function setMin($min) {
		$this->min = (int) $min;
	}

	public function setMax($max) {
		$this->max = (int) $max;
	}

	public function setStep($step) {
		$this->step = (int) $step;
	}

	public function setBounds($min, $max, $step = 1) {
		$this->setMin($min);
		$this->setMax($max);
		$this->setStep($step);
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
