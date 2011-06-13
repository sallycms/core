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
 * A list of radio boxes
 *
 * This element will present the given list of values as a list of radio boxes.
 * By nature, this element allows to select only one element, including none
 * when none is pre-selected, but gives no possiblity to revert the selection to
 * none. That's why the "display value" of this element is a string and not an
 * array of values.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Select_Radio extends sly_Form_Select_Base implements sly_Form_IElement {
	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('form/select/radio.phtml');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return string  the selected key
	 */
	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}
}
