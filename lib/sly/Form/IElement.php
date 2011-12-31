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
 * Base interface for all form elements
 *
 * @ingroup form
 * @author  Christoph
 */
interface sly_Form_IElement {
	public function getID();    ///< @return string
	public function getName();  ///< @return string
	public function getLabel(); ///< @return string
	public function getValue(); ///< @return string

	/**
	 * Adds a new CSS class
	 *
	 * This method will add a new CSS class to the element. Classes are
	 * automatically made unique.
	 *
	 * @param string $className  the CSS class
	 */
	public function addClass($className);

	/**
	 * Returns an attribute
	 *
	 * @param  string $name     the attribute's name
	 * @param  mixed  $default  the default value
	 * @return mixed            the value or the default value
	 */
	public function getAttribute($name);

	/**
	 * Sets an attribute
	 *
	 * This method will add a new attribute, if it's allowed or the name begins
	 * with "data-" (generic HTML5 attribute).
	 *
	 * @param  string $name   the attribute's name
	 * @param  mixed  $value  the new value
	 * @return boolean        true if the attribute is allowed, else false
	 */
	public function setAttribute($name, $value);

	/**
	 * Removes an attribute
	 *
	 * @param string $name  the attribute's name
	 */
	public function removeAttribute($name);

	/**
	 * Adds a new outer row class
	 *
	 * This method will add a new CSS class to the element. Classes are
	 * automatically made unique.
	 *
	 * @param string $className  the CSS class
	 */
	public function addOuterClass($className);

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass();

	/**
	 * Container check
	 *
	 * This method checks whether an element is rendering a complete form row
	 * (including the label part, if needed) or if it's just the raw element
	 * (in this case, the form instance will render the label).
	 *
	 * @return boolean
	 */
	public function isContainer();

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return string  submitted value
	 */
	public function getDisplayValue();

	/**
	 * Return language status
	 *
	 * @return boolean  true if the element is multilingual, else false
	 */
	public function isMultilingual();
}
