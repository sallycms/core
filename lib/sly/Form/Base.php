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
	 * @return sly_Form    the current object
	 */
	abstract public function addRow(array $row);

	/**
	 * Add multiple form elements at once
	 *
	 * This method can be used to add multiple elements to a form at once. Each
	 * element will be put in its own row.
	 *
	 * @param  array $elements  list of form elements (sly_Form_IElement elements)
	 * @return sly_Form         the current object
	 */
	public function addElements(array $elements) {
		foreach (array_filter($elements) as $element) {
			$this->addRow(array($element));
		}

		return $this;
	}

	/**
	 * Add a single form element
	 *
	 * This method adds a single form element using a new row. It's mostly an
	 * alias for add().
	 *
	 * @see    add()
	 * @param  sly_Form_IElement $element  the element to add
	 * @return sly_Form                    the current object
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
	 * @return sly_Form                    the current object
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
	 * @return sly_Form     the current object
	 */
	public function addRows(array $rows) {
		foreach (array_filter($rows) as $row) {
			$this->addRow($row);
		}

		return $this;
	}

	/**
	 * Returns the form as rendered XHTML
	 *
	 * This is just a convenience wrapper.
	 *
	 * @return string  the rendered form
	 */
	public function __toString() {
		return $this->render();
	}

	/**
	 * Adds or overwrites a new hidden value to the form
	 *
	 * The given value will be automatically XHTML encoded (so give the raw
	 * value!).
	 *
	 * @param  string $name   the element's name
	 * @param  string $value  the value
	 * @param  string $id     an optional ID for the <input> tag
	 * @return sly_Form       the current object
	 */
	public function addHiddenValue($name, $value, $id = null) {
		$this->hiddenValues[$name] = array('value' => $value, 'id' => $id);

		return $this;
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

	/**
	 * Get the full path for a view
	 *
	 * This methods prepends the filename of a specific view with its path. If
	 * the view is not found inside the core, an exception is thrown.
	 *
	 * @throws sly_Form_Exception  if the view could not be found
	 * @param  string $file        the relative filename
	 * @return string              the full path to the view file
	 */
	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/form/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Form_Exception(t('view_not_found', $file));
	}
}
