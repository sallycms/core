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
 * Medialist button
 *
 * This element will render a special widget that allows the user to select
 * a list of files from the mediapool.
 *
 * Elements will be called 'REX_MEDIALIST_X', where X is the running widget ID.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_MediaListButton extends sly_Form_Widget implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param array  $value  the current value (a list of filenames)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id, 'medialistbutton');
		$this->setAttribute('class', 'rex-form-select');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/medialistbutton.phtml');
	}

	/**
	 * Returns the element ID
	 *
	 * @see    getWidgetID()
	 * @return string  a string like 'REX_MEDIALIST_X', with X being the running widget ID
	 */
	public function getID() {
		return 'REX_MEDIALIST_'.$this->getWidgetID();
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return array  a list of filenames
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', true);
	}
}
