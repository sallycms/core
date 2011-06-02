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
 * Base class for all forms
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Base extends sly_Viewable {
	protected $hiddenValues;  ///< array  assoc. list of hidden form values

	/**
	 * Adds a new row with elements to the form
	 *
	 * This method can be used to add a row to a form. It's the most general form
	 * of adding elements and therefore the only method an implementation has to
	 * implement itself.
	 *
	 * @param  array $row  list of form elements (sly_Form_IElement elements)
	 * @return boolean     always true
	 */
	abstract public function addRow(array $row);

	/**
	 * Add multiple form elements at once
	 *
	 * This method can be used to add multiple elements to a form at once. Each
	 * element will be put in its own row.
	 *
	 * @param  array $elements  list of form elements (sly_Form_IElement elements)
	 * @return boolean          true if everything worked, else false
	 */
	public function addElements(array $elements) {
		$success = true;
		foreach (array_filter($elements) as $element) {
			$success &= $this->addRow(array($element));
		}
		return $success;
	}

	/**
	 * Add a single form element
	 *
	 * This method adds a single form element using a new row. It's mostly an
	 * alias for add().
	 *
	 * @see    add()
	 * @param  sly_Form_IElement $element  the element to add
	 * @return boolean                     true if it worked, else false
	 */
	public function addElement(sly_Form_IElement $element) {
		return $this->addRow(array($element));
	}

	/**
	 * Add a single form element
	 *
	 * This method adds a single form element using a new row.
	 *
	 * @param  sly_Form_IElement $element  the element to add
	 * @return boolean                     true if it worked, else false
	 */
	public function add(sly_Form_IElement $element) {
		return $this->addRow(array($element));
	}

	/**
	 * Add multiple form rows at once
	 *
	 * This method can be used to add multiple rows to a form at once.
	 *
	 * @param  array $rows  list of form rows (each an array of sly_Form_IElement elements)
	 * @return boolean      true if everything worked, else false
	 */
	public function addRows(array $rows) {
		$success = true;

		foreach (array_filter($rows) as $row) {
			$success &= $this->addRow($row);
		}

		return $success;
	}

	/**
	 * Returns the form as rendered XHTML
	 *
	 * This is just a convenience wrapper for calling render() with false.
	 *
	 * @return string  the rendered form
	 */
	public function __toString() {
		return $this->render(false);
	}

	/**
	 * Adds or overwrites a new hidden value to the form
	 *
	 * The given value will be automatically XHTML encoded (so give the raw
	 * value!).
	 *
	 * @param string $name   the element's name
	 * @param string $value  the value
	 * @param string $id     an optional ID for the <input> tag
	 */
	public function addHiddenValue($name, $value, $id = null) {
		$this->hiddenValues[$name] = array('value' => $value, 'id' => $id);
	}

	/**
	 * Check if the form is multilingual
	 *
	 * This method iterates through all rows and checks each element for its
	 * language status. When the first multilingual element is found, the method
	 * exits and returns true.
	 *
	 * You can give this method a list of form elements, to only check the list.
	 * Else it will check all rows in this instance.
	 *
	 * @param  array $row  a list of form elements
	 * @return boolean     true if at least one element is multilingual, else false
	 */
	public function isMultilingual(array $row = null) {
		$rows = $row ? array($row) : $this->rows;

		foreach ($rows as $row) {
			foreach ($row as $element) {
				if ($element->isMultilingual()) return true;
			}
		}

		return false;
	}

	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/form/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exception('View '.$file.' could not be found.');
	}
}
