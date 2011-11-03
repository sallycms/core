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
 * A list of checkboxes
 *
 * This element will present the given list of values as a list of checkboxes,
 * including convenience methods for selecting all/none elements. By nature,
 * this element allows to select multiple elements, including none.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Select_Checkbox extends sly_Form_Select_Base implements sly_Form_IElement {
	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('element/select/checkbox.phtml');
	}
}
