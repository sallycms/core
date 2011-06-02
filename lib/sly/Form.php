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
 * @ingroup form
 */
class sly_Form extends sly_Form_Base {
	protected $action;
	protected $method;
	protected $title;
	protected $name;
	protected $id;
	protected $classes;
	protected $enctype;
	protected $submitButton;
	protected $resetButton;
	protected $deleteButton;
	protected $applyButton;
	protected $fieldsets;
	protected $currentFieldset;
	protected $focussedElement;
	protected $buttonClasses;

	/**
	 * @param string $action
	 * @param string $method
	 * @param string $title
	 * @param string $name
	 * @param string $id
	 */
	public function __construct($action, $method = 'POST', $title, $name = '', $id = '') {
		$this->action   = $action;
		$this->method   = strtoupper($method) == 'GET' ? 'GET' : 'POST';
		$this->title    = $title;
		$this->name     = $name;
		$this->id       = $id;
		$this->enctype  = false;
		$this->classes  = array();

		$this->submitButton    = new sly_Form_Input_Button('submit', 'submit', 'Speichern');
		$this->resetButton     = new sly_Form_Input_Button('reset', 'reset', 'Zurücksetzen');
		$this->deleteButton    = null;
		$this->applyButton     = null;
		$this->hiddenValues    = array();
		$this->fieldsets       = array();
		$this->currentFieldset = null;
		$this->focussedElement = '';
		$this->buttonClasses   = array('submit' => array(), 'reset' => array(), 'delete' => array(), 'apply' => array());
	}

	/**
	 * @param string $enctype
	 */
	public function setEncType($enctype) {
		$this->enctype = trim($enctype);
	}

	/**
	 * @param  string $title
	 * @param  string $id
	 * @param  int    $columns
	 * @return sly_Form_Fieldset
	 */
	public function beginFieldset($title, $id = null, $columns = 1) {
		$this->currentFieldset = new sly_Form_Fieldset($title, $id, $columns);
		$this->fieldsets[]     = $this->currentFieldset;

		return $this->currentFieldset;
	}

	/**
	 * @param  array $row
	 * @return boolean  always true
	 */
	public function addRow(array $row) {
		if ($this->currentFieldset === null) {
			$this->beginFieldset($this->title);
		}

		$this->currentFieldset->addRow($row);
		return true;
	}

	/**
	 * @param sly_Form_Fieldset $fieldset
	 */
	public function addFieldset(sly_Form_Fieldset $fieldset) {
		$this->fieldsets[]     = $fieldset;
		$this->currentFieldset = null;
	}

	public function setSubmitButton(sly_Form_Input_Button $submitButton = null) {
		$this->submitButton = $submitButton;
	}

	public function setResetButton(sly_Form_Input_Button $resetButton = null) {
		$this->resetButton = $resetButton;
	}

	public function setApplyButton(sly_Form_Input_Button $applyButton = null) {
		$this->applyButton = $applyButton;
	}

	public function setDeleteButton(sly_Form_Input_Button $deleteButton = null) {
		$this->deleteButton = $deleteButton;
	}

	public function getSubmitButton() { return $this->submitButton; } ///< @return sly_Form_Input_Button
	public function getResetButton()  { return $this->resetButton;  } ///< @return sly_Form_Input_Button
	public function getApplyButton()  { return $this->applyButton;  } ///< @return sly_Form_Input_Button
	public function getDeleteButton() { return $this->deleteButton; } ///< @return sly_Form_Input_Button

	/**
	 * @param string $type
	 * @param string $class
	 */
	public function addButtonClass($type, $class) {
		$this->buttonClasses[$type][] = trim($class);
		$this->buttonClasses[$type]   = array_unique($this->buttonClasses[$type]);
	}

	/**
	 * Render the form
	 *
	 * Renders the form and prints it by default. Change $print to false to get
	 * the generated XHTML returned.
	 *
	 * @param  boolean $print  if false, the generated XHTML is returned
	 * @return mixed           null if $print is true, else the XHTML (string)
	 */
	public function render($print = true, $omitFormTag = false) {
		if (!$print) ob_start();
		$this->renderView('form.phtml', array('form' => $this, 'omitFormTag' => $omitFormTag));
		if (!$print) return ob_get_clean();
	}

	public function clearElements() {
		$this->fieldsets       = array();
		$this->currentFieldset = null;
	}

	/**
	 * @param string $elementID
	 */
	public function setFocus($elementID) {
		$this->focussedElement = $elementID;
	}

	/**
	 * @return sly_Form_Fieldset
	 */
	public function getCurrentFieldset() {
		return $this->currentFieldset;
	}

	/**
	 * @param string $class
	 * @return array
	 */
	public function addClass($class) {
		$class = explode(' ', $class);
		foreach ($class as $c) $this->classes[] = $c;
		$this->classes = array_unique($this->classes);
		return $this->classes;
	}

	public function clearClasses() {
		$this->classes = array();
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}
}
