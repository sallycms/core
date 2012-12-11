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
 * A backend form
 *
 * This class renders the normal backend form, used on many Sally pages. The
 * form consists of multiple fieldsets (beginning with a implicitly created one)
 * and can also contain hidden values. It also supports direct access to all
 * relevant four buttons (submit, reset, delete, apply).
 *
 * If you want to build you own form, this is the class you want to use.
 *
 * @see     http://docs.webvariants.de/sallycms/latest/sallycms/forms/index.html
 * @ingroup form
 * @author  Christoph
 */
class sly_Form extends sly_Form_Base {
	protected $action;           ///< string
	protected $method;           ///< string
	protected $title;            ///< string
	protected $name;             ///< string
	protected $id;               ///< string
	protected $classes;          ///< array
	protected $enctype;          ///< string
	protected $submitButton;     ///< sly_Form_Input_Button
	protected $resetButton;      ///< sly_Form_Input_Button
	protected $deleteButton;     ///< sly_Form_Input_Button
	protected $applyButton;      ///< sly_Form_Input_Button
	protected $fieldsets;        ///< array
	protected $currentFieldset;  ///< sly_Form_Fieldset
	protected $focussedElement;  ///< string
	protected $buttonClasses;    ///< array
	protected $addCsrfToken;     ///< boolean

	/**
	 * Constructor
	 *
	 * Creates a new form and the first fieldset. It also sets up the submit and
	 * reset button.
	 *
	 * @param string $action  the action (in most cases, a URL like 'index.php')
	 * @param string $method  the HTTP method (GET or POST)
	 * @param string $title   the form title (is the title of the first fieldset)
	 * @param string $name    the form name (optional)
	 * @param string $id      the form ID (optional)
	 */
	public function __construct($action, $method, $title, $name = '', $id = '') {
		$this->action   = $action;
		$this->method   = strtoupper($method) === 'GET' ? 'GET' : 'POST';
		$this->title    = $title;
		$this->name     = $name;
		$this->id       = $id;
		$this->enctype  = false;
		$this->classes  = array();

		$this->submitButton    = new sly_Form_Input_Button('submit', 'submit', t('save'));
		$this->resetButton     = new sly_Form_Input_Button('reset', 'reset', t('reset'));
		$this->deleteButton    = null;
		$this->applyButton     = null;
		$this->hiddenValues    = array();
		$this->fieldsets       = array();
		$this->currentFieldset = null;
		$this->focussedElement = '';
		$this->buttonClasses   = array('submit' => array('sly-form-submit'), 'reset' => array(), 'delete' => array('sly-form-submit'), 'apply' => array('sly-form-submit'));
		$this->addCsrfToken    = true;
	}

	/**
	 * Get the form's method (GET or POST)
	 *
	 * @return string  the method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Set the encoding
	 *
	 * Use this method to alter the encoding, for example when you use the form
	 * to upload files.
	 *
	 * @param  string $enctype  the new enctype
	 * @return sly_Form         the current object
	 */
	public function setEncType($enctype) {
		$this->enctype = trim($enctype);
		return $this;
	}

	/**
	 * Start a new fieldset
	 *
	 * This method creates a new fieldset, appends it to the form and marks it
	 * as active (that means that new elements will be appended to this one).
	 * The fieldset can use more than one column, independently from the other
	 * fieldsets in this form. Note that fieldsets with more than one column
	 * cannot contain multilingual form elements (because the XHTML would be
	 * so darn complex, that we just disabled this option).
	 *
	 * @param  string $title      the fieldset title
	 * @param  string $id         the HTML id
	 * @param  int    $columns    the number of columns
	 * @return sly_Form_Fieldset  the newly created fieldset
	 */
	public function beginFieldset($title, $id = null, $columns = 1) {
		$this->currentFieldset = new sly_Form_Fieldset($title, $id, $columns);
		$this->fieldsets[]     = $this->currentFieldset;

		return $this->currentFieldset;
	}

	/**
	 * Adds a new row of elements
	 *
	 * This method will add a new row to the currently active fieldset. In most
	 * cases, this is the last one, that has been created.
	 *
	 * @param  array $row  the array of form elements
	 * @return sly_Form    the current object
	 */
	public function addRow(array $row) {
		if ($this->currentFieldset === null) {
			$this->beginFieldset($this->title);
		}

		$this->currentFieldset->addRow($row);
		return $this;
	}

	/**
	 * Adds a new fieldset
	 *
	 * This methods just adds a new fieldset to the form and marks it as active.
	 *
	 * @param  sly_Form_Fieldset $fieldset  the fieldset to add
	 * @return sly_Form                     the current object
	 */
	public function addFieldset(sly_Form_Fieldset $fieldset) {
		$this->fieldsets[]     = $fieldset;
		$this->currentFieldset = null;

		return $this;
	}

	/**
	 * Set the submit button
	 *
	 * This method allows access to the special submit button. Use it to
	 * overwrite the default button with your own (giving a new button) or to
	 * remove the button (giving null).
	 *
	 * @param  sly_Form_Input_Button $submitButton  the new submit button
	 * @return sly_Form                             the current object
	 */
	public function setSubmitButton(sly_Form_Input_Button $submitButton = null) {
		$this->submitButton = $submitButton;

		return $this;
	}

	/**
	 * Set the reset button
	 *
	 * This method allows access to the special reset button. Use it to
	 * overwrite the default button with your own (giving a new button) or to
	 * remove the button (giving null).
	 *
	 * @param  sly_Form_Input_Button $resetButton  the new reset button
	 * @return sly_Form                            the current object
	 */
	public function setResetButton(sly_Form_Input_Button $resetButton = null) {
		$this->resetButton = $resetButton;

		return $this;
	}

	/**
	 * Set the apply button
	 *
	 * This method allows access to the special apply button. Use it to
	 * overwrite the default button with your own (giving a new button) or to
	 * remove the button (giving null).
	 * This button does not exist by default.
	 *
	 * @param  sly_Form_Input_Button $applyButton  the new apply button
	 * @return sly_Form                            the current object
	 */
	public function setApplyButton(sly_Form_Input_Button $applyButton = null) {
		$this->applyButton = $applyButton;

		return $this;
	}

	/**
	 * Set the delete button
	 *
	 * This method allows access to the special delete button. Use it to
	 * overwrite the default button with your own (giving a new button) or to
	 * remove the button (giving null).
	 * This button does not exist by default.
	 *
	 * @param  sly_Form_Input_Button $deleteButton  the new delete button
	 * @return sly_Form                             the current object
	 */
	public function setDeleteButton(sly_Form_Input_Button $deleteButton = null) {
		$this->deleteButton = $deleteButton;

		return $this;
	}

	/**
	 * Returns the submit button
	 *
	 * @return sly_Form_Input_Button  the submit button
	 */
	public function getSubmitButton() {
		return $this->submitButton;
	}

	/**
	 * Returns the reset button
	 *
	 * @return sly_Form_Input_Button  the reset button
	 */
	public function getResetButton() {
		return $this->resetButton;
	}

	/**
	 * Returns the apply button
	 *
	 * @return sly_Form_Input_Button  the apply button
	 */
	public function getApplyButton() {
		return $this->applyButton;
	}

	/**
	 * Returns the delete button
	 *
	 * @return sly_Form_Input_Button  the delete button
	 */
	public function getDeleteButton() {
		return $this->deleteButton;
	}

	/**
	 * Adds a new CSS class to a button
	 *
	 * This methods adds a class to a specific button. $type can be 'submit',
	 * 'reset', 'delete' or 'apply'. The list of classes per type will be unique.
	 *
	 * @param  string $type   the button type (submit, reset, delete or apply)
	 * @param  string $class  the new CSS class
	 * @return sly_Form       the current object
	 */
	public function addButtonClass($type, $class) {
		$this->buttonClasses[$type][] = trim($class);
		$this->buttonClasses[$type]   = array_unique($this->buttonClasses[$type]);

		return $this;
	}

	/**
	 * Render the form
	 *
	 * Renders the form and returns its content.
	 *
	 * @param  boolean     $omitFormTag  set this to true if you want to use your own <form> tag
	 * @param  sly_Request $request      the request to use or null for the global one
	 * @return string                    the generated XHTML
	 */
	public function render($omitFormTag = false, sly_Request $request = null) {
		if ($this->addCsrfToken) {
			sly_Util_Csrf::prepareForm($this);
		}

		$request = $request ? $request : sly_Core::getRequest();
		return $this->renderView('form.phtml', array('form' => $this, 'omitFormTag' => $omitFormTag, 'request' => $request));
	}

	/**
	 * Remove all elements
	 *
	 * This method will remove all fieldsets from the form and reset the active
	 * fieldset to 'none'. This will make any add* method create a new fieldset
	 * when called.
	 *
	 * @return sly_Form  the current object
	 */
	public function clearElements() {
		$this->fieldsets       = array();
		$this->currentFieldset = null;

		return $this;
	}

	/**
	 * Sets the focus
	 *
	 * This method sets the focus to one element, generating a bit of jQuery code
	 * to set the cursor to it when the form is rendered.
	 *
	 * @param  mixed $element  the instance or ID of the element to focus
	 * @return sly_Form        the current object
	 */
	public function setFocus($element) {
		$this->focussedElement = $element;

		return $this;
	}

	/**
	 * Get the current fieldset
	 *
	 * @return sly_Form_Fieldset  the current fieldset (or null after the form has been created or cleared)
	 */
	public function getCurrentFieldset() {
		return $this->currentFieldset;
	}

	/**
	 * Get all fieldsets
	 *
	 * @return array  list of all fieldsets
	 */
	public function getFieldsets() {
		return $this->fieldsets;
	}

	/**
	 * Get element by name
	 *
	 * @param  string $name       the element's name
	 * @return sly_Form_IElement  the found element or null
	 */
	public function findElementByName($name) {
		return $this->findElement('name', $name);
	}

	/**
	 * Get element by ID
	 *
	 * @param  string $name       the element's ID
	 * @return sly_Form_IElement  the found element or null
	 */
	public function findElementByID($id) {
		return $this->findElement('id', $id);
	}

	/**
	 * Get element by name
	 *
	 * @param  string $criterium  'id' or 'name'
	 * @param  string $value      the value to find
	 * @return sly_Form_IElement  the found element or null
	 */
	protected function findElement($criterium, $value) {
		foreach ($this->fieldsets as $fieldset) {
			foreach ($fieldset->getRows() as $row) {
				foreach ($row as $element) {
					if ($element instanceof sly_Form_IElement) {
						if ($criterium === 'name' && $element->getName() === $value) return $element;
						if ($criterium === 'id'   && $element->getID() === $value)   return $element;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Replace an existing element
	 *
	 * @param  string $criterium  'id' or 'name'
	 * @param  string $value      the value to find
	 * @param  sly_Form_IElement  the new element
	 * @return sly_Form_IElement  the replaced element or null if not found
	 */
	public function replaceElement($criterium, $value, sly_Form_IElement $element) {
		foreach ($this->fieldsets as $fIdx => $fieldset) {
			foreach ($fieldset->getRows() as $rowIdx => $row) {
				foreach ($row as $elemIdx => $elem) {

					if ($elem instanceof sly_Form_IElement) {
						if (
							($criterium === 'name' && $elem->getName() === $value) ||
							($criterium === 'id'   && $elem->getID()   === $value)
						) {
							return $fieldset->replaceElement($rowIdx, $elemIdx, $element);
						}
					}
				}
			}
		}

		return null;
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
		$class = explode(' ', $class);
		foreach ($class as $c) $this->classes[] = $c;
		$this->classes = array_unique($this->classes);

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
		$this->classes = array();

		return $this;
	}

	/**
	 * Get all classes
	 *
	 * @return array  the list of CSS classes for this form
	 */
	public function getClasses() {
		return $this->classes;
	}

	public function setCsrfEnabled($flag = true) {
		$this->addCsrfToken = (boolean) $flag;
		return $this;
	}
}
