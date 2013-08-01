<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Generic select wrapper
 *
 * Selects do not need to be <select> elements, but instead they offer a choice
 * between elements of a predefined list. This includes radio- and check boxes.
 *
 * This class wraps a concrete select element and allows to change the style
 * after the element has been created.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Select extends sly_Form_Select_Base implements sly_Form_IElement {
	protected $formElement; ///< sly_Form_Select_Base  the wrapped element

	const STYLE_DROPDOWN = 0; ///< int
	const STYLE_RADIO    = 1; ///< int
	const STYLE_CHECKBOX = 2; ///< int

	/**
	 * Constructor
	 *
	 * Creates a selectbox by default.
	 *
	 * @param string $name    element name
	 * @param string $label   the label
	 * @param array  $value   the currently selected elements
	 * @param array  $values  list of available values
	 * @param string $id      optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value, $values, $id = null) {
		parent::__construct($name, $label, $value, $values, $id);
		$this->setStyle(self::STYLE_DROPDOWN);
	}

	/**
	 * Sets a new style for the wrapped element
	 *
	 * @param  int $newStyle         the new style (see constants in this class)
	 * @return sly_Form_Select_Base  a newly created element
	 */
	public function setStyle($newStyle) {
		$name   = $this->attributes['name'];
		$label  = $this->label;
		$value  = $this->attributes['value'];
		$values = $this->values;
		$id     = $this->attributes['id'];

		switch ($newStyle) {
			case self::STYLE_CHECKBOX:
				$this->formElement = new sly_Form_Select_Checkbox($name, $label, $value, $values, $id);
				break;

			case self::STYLE_RADIO:
				$this->formElement = new sly_Form_Select_Radio($name, $label, $value, $values, $id);
				break;

			default:
				$this->formElement = new sly_Form_Select_DropDown($name, $label, $value, $values, $id);
				break;
		}

		return $this->formElement;
	}

	/**
	 * Renders the wrapped element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->formElement->render();
	}

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass() {
		return $this->formElement->getOuterClass();
	}
}
