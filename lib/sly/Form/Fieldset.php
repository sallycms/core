<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Form fieldset
 *
 * Forms consists of a series of fieldsets, which in turn contain the form
 * elements. Each fieldset has a legend and an incrementing ID. Every form has
 * one empty fieldset upon creation.
 *
 * Fieldsets can consist of multiple columns per row. In this case, it's not
 * possible to inject multilingual elements, as they would screw up the layout.
 *
 * Although the implementation allows for up to 26 columns, only single and
 * double column fieldsets are correctly styled and should be used.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Fieldset extends sly_Viewable {
	protected $rows;     ///< array
	protected $num;      ///< int
	protected $columns;  ///< int
	protected $legend;   ///< string
	protected $attrs;    ///< array

	/**
	 * Constructor
	 *
	 * @param string $legend   the fieldset's legend
	 * @param string $id       the HTML id
	 * @param int    $columns  number of columns (use 1 or 2, more won't give you any useful results yet)
	 * @param int    $num      the running number of this fieldset
	 */
	public function __construct($legend, $id = '', $columns = 1, $num = -1) {
		$this->rows    = array();
		$this->columns = $columns;
		$this->legend  = $legend;
		$this->attrs   = compact('id');

		$this->setNum($num);
	}

	/**
	 * Adds a single row to the fieldset
	 *
	 * This method adds a row containing the form elements to the fieldset.
	 *
	 * @throws sly_Form_Exception  if the form has multiple columns and one element is multilingual
	 * @param  array $row          array containing the form elements
	 * @return sly_Form_Fieldset   the object itself
	 */
	public function addRow(array $row) {
		if ($this->columns > 1 && $this->isMultilingual($row)) {
			throw new sly_Form_Exception(t('cannot_insert_multilingual_element_in_multicolumn_fieldset'));
		}

		$this->rows[] = $row;
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
	 * Add multiple form rows at once
	 *
	 * This method can be used to add multiple rows to a form at once.
	 *
	 * @param  array $rows        list of form rows (each an array of sly_Form_IElement elements)
	 * @return boolean            true if everything worked, else false
	 * @return sly_Form_Fieldset  the object itself
	 */
	public function addRows(array $rows) {
		foreach (array_filter($rows) as $row) {
			$this->addRow(sly_makeArray($row));
		}

		return $this;
	}

	/**
	 * Replace an existing element
	 *
	 * @param  int $rowIdx        the row number (0-based)
	 * @param  int $columnIdx     the column number (0-based) (always 0 for single-column fieldsets)
	 * @param  sly_Form_IElement  the new element
	 * @return sly_Form_IElement  the replaced element or null if not found
	 */
	public function replaceElement($rowIdx, $columnIdx, sly_Form_IElement $element) {
		$rowIdx    = (int) $rowIdx;
		$columnIdx = (int) $columnIdx;

		if (!isset($this->rows[$rowIdx][$columnIdx])) {
			return null;
		}

		$old = $this->rows[$rowIdx][$columnIdx];
		$this->rows[$rowIdx][$columnIdx] = $element;

		return $old;
	}

	/**
	 * Render the form
	 *
	 * Renders the form and returns the generated XHTML.
	 *
	 * @param  sly_Request $request  the request to use or null for the global one
	 * @return string                the XHTML
	 */
	public function render(sly_Request $request = null) {
		$request = $request ? $request : sly_Core::getRequest();
		return $this->renderView('fieldset.phtml', compact('request'));
	}

	/**
	 * Remove all rows
	 *
	 * @return sly_Form_Fieldset  the object itself
	 */
	public function clearRows() {
		$this->rows = array();
		return $this;
	}

	public function getRows()    { return $this->rows;    } ///< @return array
	public function getNum()     { return $this->num;     } ///< @return int
	public function getColumns() { return $this->columns; } ///< @return int
	public function getLegend()  { return $this->legend;  } ///< @return string

	/**
	 * @return string
	 */
	public function getID() {
		return $this->getAttribute('id', '');
	}

	/**
	 * @param  string $name
	 * @param  mixed  $value
	 * @return sly_Form_Fieldset  the object itself
	 */
	public function setAttribute($name, $value) {
		$this->attrs[$name] = is_string($value) ? trim($value) : $value;
		return $this;
	}

	/**
	 * @param  string $name
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function getAttribute($name, $default = null) {
		return isset($this->attrs[$name]) ? $this->attrs[$name] : $default;
	}

	/**
	 * Sets the number of columns
	 *
	 * @throws sly_Form_Exception  if the form has multiple columns and one element is multilingual
	 * @param  int $num            number of columns, ranging from 1 to 26
	 * @return sly_Form_Fieldset   the object itself
	 */
	public function setColumns($num) {
		$num = ($num > 0 && $num < 26) ? $num : 1;

		if ($num > 1 && $this->isMultilingual()) {
			throw new sly_Form_Exception(t('fieldset_contains_multilingual_elements_has_to_be_singlecolumn'));
		}

		$this->columns = $num;
		return $this;
	}

	/**
	 * Sets the legend
	 *
	 * @param  string $legend     the new legend
	 * @return sly_Form_Fieldset  the object itself
	 */
	public function setLegend($legend) {
		$this->legend = trim($legend);
		return $this;
	}

	/**
	 * Sets the ID
	 *
	 * @param  string $id  the new id
	 * @return string      the new id (trimmed)
	 */
	public function setID($id) {
		return $this->setAttribute('id', trim($id));
	}

	/**
	 * Sets the new number
	 *
	 * The number will be put in a special CSS class, so that you can style each
	 * fieldset accordingly. Give -1 to generate an automatically incremented
	 * number (the default), or give a concrete number to set it.
	 *
	 * The current fieldset number is stored in the temporary registry under the
	 * key 'sly.form.fieldset.num'.
	 *
	 * @param  int $num           the new number
	 * @return sly_Form_Fieldset  the object itself
	 */
	public function setNum($num = -1) {
		$registry = sly_Core::getTempRegistry();
		$key      = 'sly.form.fieldset.num';

		if ($num <= 0) {
			$num = $registry->has($key) ? ($registry->get($key) + 1) : 1;
		}
		else {
			$num = (int) $num;
		}

		$this->num = $num;
		$registry->set($key, $num);

		return $this;
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

	/**
	 * Add a new class to the form
	 *
	 * This method will add a CSS class to the form tag. You can give mutliple
	 * classes at once, the method will split them up and add them one at a time,
	 * ensuring that they are unique.
	 *
	 * @param  string $class  the CSS class
	 * @return sly_Form       the current object
	 */
	public function addClass($class) {
		$old     = $this->getAttribute('class', '');
		$classes = array_unique(array_filter(explode(' ', $class.' '.$old)));

		$this->setAttribute('class', implode(' ', $classes));

		return $this;
	}

	/**
	 * Remove all classes
	 *
	 * This method removes all set CSS classes for this form.
	 *
	 * @return sly_Form  the current object
	 */
	public function clearClasses() {
		$this->setAttribute('class', '');

		return $this;
	}

	/**
	 * Get all classes
	 *
	 * @return array  the list of CSS classes for this form
	 */
	public function getClasses() {
		return explode(' ', $this->getAttribute('class', ''));
	}
}
