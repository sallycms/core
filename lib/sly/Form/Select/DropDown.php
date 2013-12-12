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
 * A classic dropdown box
 *
 * This element will present the given list of values as a dropdown box.
 * This is the most common selection type and allows multiple or single selects,
 * using varying sizes. The wrapped value is always an array, even when the
 * box using single selection.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Select_DropDown extends sly_Form_Select_Base implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name    element name
	 * @param string $label   the label
	 * @param array  $value   the currently selected elements
	 * @param array  $values  list of available values
	 * @param string $id      optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value, array $values, $id = null) {
		parent::__construct($name, $label, $value, $values, $id);
		$this->addClass('sly-form-select');
	}

	/**
	 * Sets the size of the dropdown box
	 *
	 * The size attribute is mainly useful when using a multi select dropdown
	 * box, as elements with a size > 1 only waste space when only one can be
	 * selected at a time.
	 *
	 * @param  int $size                 the new size
	 * @return sly_Form_Select_DropDown  the object itself
	 */
	public function setSize($size) {
		return $this->setAttribute('size', (int) $size);
	}

	/**
	 * Enable or disable multi selects
	 *
	 * Use this method to toggle the "multiple" attribute.
	 *
	 * @param  boolean $multiple         true to enable, false to disable multi selection
	 * @return sly_Form_Select_DropDown  the object itself
	 */
	public function setMultiple($multiple) {
		if ($multiple) return $this->setAttribute('multiple', 'multiple');
		else return $this->removeAttribute('multiple');
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('element/select/dropdown.phtml');
	}
}
