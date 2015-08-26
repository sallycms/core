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
 * A list of submit buttons
 *
 * This class wraps a special form element that contains no label, but instead
 * holds a list of buttons (usually you will use this with submit, delete,
 * apply and reset buttons).
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_ButtonBar extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $buttons;  ///< array  list of buttons (sly_Form_Input_Button elements)

	/**
	 * Constructor
	 *
	 * @param array  $buttons  list of buttons (or a single button)
	 * @param string $id       the optional ID for the complete row
	 */
	public function __construct($buttons = array(), $id = null) {
		$id = $id === null ? 'a'.uniqid() : $id;
		parent::__construct('', '', '', $id);
		$this->buttons = sly_makeArray($buttons);
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('element/buttonbar.phtml');
	}

	/**
	 * Container check
	 *
	 * This method checks whether an element is rendering a complete form row
	 * (including the label part, if needed) or if it's just the raw element
	 * (in this case, the form instance will render the label).
	 *
	 * @return boolean  always true
	 */
	public function isContainer() {
		return true;
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * For this element, this method is mostly useless as it will always return
	 * the button's values (which are static).
	 *
	 * @return array  list of the buttons display values
	 */
	public function getDisplayValue() {
		$name = array();
		foreach ($this->buttons as $button) $name[] = $button->getDisplayValue();
		return $name;
	}

	/**
	 * Returns the buttons
	 *
	 * @return array  list of buttons
	 */
	public function getButtons() {
		return $this->buttons;
	}

	/**
	 * Adds a new button
	 *
	 * @param  sly_Form_Input_Button $button  the new button to add
	 * @return sly_Form_ButtonBar             the object itself
	 */
	public function addButton(sly_Form_Input_Button $button) {
		$this->buttons[] = $button;
		return $this;
	}

	/**
	 * Clears the list of buttons
	 *
	 * @return sly_Form_ButtonBar  the object itself
	 */
	public function clearButtons() {
		$this->buttons = array();
		return $this;
	}
}
