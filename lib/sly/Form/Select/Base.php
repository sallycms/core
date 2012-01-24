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
 * Base class for all selection types
 *
 * This class wraps some common methods for all 3 select types (selectbox,
 * list of checkboxes and list of radio boxes).
 *
 * Selects always operate on a list of assoctiative values (that means if
 * you give them a normal, numerated list of values, the numeric indices will
 * be used as keys).
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Select_Base extends sly_Form_ElementBase {
	protected $values; ///< array  list of values

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
		parent::__construct($name, $label, $value, $id);
		$this->values = $values;
		$this->addOuterClass('sly-form-select-row');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return array  list of selected keys
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', true);
	}

	/**
	 * Sets the list of values
	 *
	 * Use this method to completely replace all values with a new list.
	 *
	 * @param array $values  the new list
	 */
	public function setValues(array $values) {
		$this->values = $values;
	}

	/**
	 * Adds a new value to the end of the list
	 *
	 * This method will add a new value or overwrite an existing one (keys will
	 * be unique).
	 *
	 * @param string $key    the key
	 * @param string $value  the value
	 */
	public function addValue($key, $value) {
		$this->values[$key] = $value;
	}

	/**
	 * Removes a value
	 *
	 * This method will remove a value, identified by its key.
	 *
	 * @param string $key  the key to remove
	 */
	public function removeValue($key) {
		unset($this->values[$key]);
	}

	/**
	 * Returns all values
	 *
	 * @return array  the current list of values
	 */
	public function getValues() {
		return $this->values;
	}

	/**
	 * Returns the number of values
	 *
	 * @return int  the number of values
	 */
	public function getValueCount() {
		return count($this->values);
	}

	/**
	 * Comfort wrapper for setting the value
	 *
	 * @return mixed $selected  the selected element (scalar or array)
	 */
	public function setSelected($selected) {
		return $this->setAttribute('value', $selected);
	}
}
