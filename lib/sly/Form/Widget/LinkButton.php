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
 * Link button
 *
 * This element will render a special widget that allows the user to select
 * one article. The article will be returned without any language information,
 * so only its ID is returned.
 * Selection will be performed in the so-called 'linkmap', a special popup for
 * browsing through the article structure.
 *
 * Elements will be called 'LINK_X', where X is the running widget ID.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_LinkButton extends sly_Form_Widget implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param string $value  the current value (an article ID)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id, 'linkbutton');
		$this->setAttribute('class', 'rex-form-text');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/linkbutton.phtml');
	}

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass() {
		$this->addOuterClass('rex-form-text');
		return $this->outerClass;
	}

	/**
	 * Returns the element ID
	 *
	 * @see    getWidgetID()
	 * @return string  a string like 'LINK_X', with X being the running widget ID
	 */
	public function getID() {
		return 'LINK_'.$this->getWidgetID();
	}

	/**
	 * Get the form element name
	 *
	 * @return string  the element name
	 */
	public function getDisplayName() {
		return $this->attributes['name'];
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return int  submitted datetime value
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('int', false);
	}
}
