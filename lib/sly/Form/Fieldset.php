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
	protected $id;       ///< string

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
		$this->id      = $id;

		$this->setNum($num);
	}

	/**
	 * Adds a single row to the fieldset
	 *
	 * This method adds a row containing the form elements to the fieldset.
	 *
	 * @throws sly_Form_Exception  if the form has multiple columns and one element is multilingual
	 * @param  array $row          array containing the form elements
	 * @return boolean             always true
	 */
	public function addRow(array $row) {
		$row = sly_makeArray($row);

		if ($this->columns > 1 && $this->isMultilingual($row)) {
			throw new sly_Form_Exception('Mehrsprachige Elemente können nicht in mehrspaltige Fieldsets eingefügt werden.');
		}

		$this->rows[] = $row;
		return true;
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
	 * @param  array $rows  list of form rows (each an array of sly_Form_IElement elements)
	 * @return boolean      true if everything worked, else false
	 */
	public function addRows(array $rows) {
		$success = true;

		foreach (array_filter($rows) as $row) {
			$success &= $this->addRow(sly_makeArray($row));
		}

		return $success;
	}

	/**
	 * Render the form
	 *
	 * Renders the form and returns the generated XHTML.
	 *
	 * @return string  the XHTML
	 */
	public function render() {
		return $this->renderView('fieldset.phtml');
	}

	/**
	 * Remove all rows
	 */
	public function clearRows() {
		$this->rows = array();
	}

	public function getRows()    { return $this->rows;    } ///< @return array
	public function getNum()     { return $this->num;     } ///< @return int
	public function getColumns() { return $this->columns; } ///< @return int
	public function getLegend()  { return $this->legend;  } ///< @return string
	public function getID($id)   { return $this->id;      } ///< @return string

	/**
	 * Sets the number of columns
	 *
	 * @throws sly_Form_Exception  if the form has multiple columns and one element is multilingual
	 * @param  int $num            number of columns, ranging from 1 to 26
	 * @return int                 the new number of columns
	 */
	public function setColumns($num) {
		$num = ($num > 0 && $num < 26) ? $num : 1;

		if ($num > 1 && $this->isMultilingual()) {
			throw new sly_Form_Exception('Dieses Fieldset enthält mehrsprachige Elemente und muss daher einspaltig sein.');
		}

		$this->columns = $num;
		return $this->columns;
	}

	/**
	 * Sets the legend
	 *
	 * @param  string $legend  the new legend
	 * @return string          the new legend (trimmed)
	 */
	public function setLegend($legend) {
		$this->legend = trim($legend);
		return $this->legend;
	}

	/**
	 * Sets the ID
	 *
	 * @param  string $id  the new id
	 * @return string      the new id (trimmed)
	 */
	public function setID($id) {
		$this->id = trim($id);
		return $this->id;
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
	 * @param  int $num  the new number
	 * @return int       the new number
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

		return $num;
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

		throw new sly_Form_Exception('View '.$file.' could not be found.');
	}
}
