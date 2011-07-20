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
 * Media button
 *
 * This element will render a special widget that allows the user to select
 * a file from the mediapool. The handled value is the file's name, not its ID.
 *
 * Elements will be called 'REX_MEDIA_X', where X is the running widget ID.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_MediaButton extends sly_Form_Widget implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param string $value  the current value (a filename)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id, 'mediabutton');
		$this->setAttribute('class', 'rex-form-text');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/mediabutton.phtml');
	}

	/**
	 * Returns the element ID
	 *
	 * @see    getWidgetID()
	 * @return string  a string like 'REX_MEDIA_X', with X being the running widget ID
	 */
	public function getID() {
		return 'REX_MEDIA_'.$this->getWidgetID();
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return string  the submitted filename
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}
}
