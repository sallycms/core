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
 * Base class for all singular line inputs
 *
 * This class wraps the base implementation for all <input> elements (since most
 * inputs are just variations with different type attributes).
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Input_Base extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $annotation;  ///< string  text that will be shown right next to the input
	protected $placeholder; ///< string  HTML5 placeholder attribute

	/**
	 * Constructor
	 *
	 * @param string $name    element name
	 * @param string $label   the label
	 * @param array  $value   the current text
	 * @param string $id      optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->annotation = '';
	}

	/**
	 * Renders the element
	 *
	 * This method renders the element.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$this->addClass('rex-form-text');

		$this->attributes['value']       = $this->getDisplayValue();
		$this->attributes['placeholder'] = $this->placeholder;

		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />'.($this->annotation ? ' '.$this->annotation : '');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return string  submitted text
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}

	/**
	 * Sets the maximum length allowed
	 *
	 * Give a value of '0' to remove the maxlength attribute.
	 *
	 * @param int $maxLength  the new max length
	 */
	public function setMaxLength($maxLength) {
		$maxLength = abs(intval($maxLength));

		if ($maxLength > 0) {
			$this->setAttribute('maxlength', $maxLength);
		}
		else {
			$this->removeAttribute('maxlength');
		}
	}

	/**
	 * Sets the input to read-only
	 *
	 * @param boolean $readonly  true to set the attribute, false to remove it
	 */
	public function setReadOnly($readonly = true) {
		if ($readonly) {
			$this->setAttribute('readonly', 'readonly');
		}
		else {
			$this->removeAttribute('readonly');
		}
	}

	/**
	 * Sets the size
	 *
	 * @param int $size  the new size
	 */
	public function setSize($size) {
		$this->setAttribute('size', abs((int) $size));
	}

	/**
	 * Sets the annotation
	 *
	 * The annotation is a little text that is shown on the right side directly
	 * after the <input> element. It's mostly used for units and other little
	 * hints.
	 *
	 * Make sure that the element has a fixed length / size, since by default the
	 * input field will take up the complete form row.
	 *
	 * @param string $annotation  the new annotation
	 */
	public function setAnnotation($annotation) {
		$this->annotation = trim($annotation);
	}

	/**
	 * Sets the placeholder
	 *
	 * This method sets the placeholder attribute, defined in HTML5.
	 *
	 * @param string $placeholder  the new placeholder
	 */
	public function setPlaceholder($placeholder) {
		$this->placeholder = trim($placeholder);
	}
}
