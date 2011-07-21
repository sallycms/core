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
 * Free form element content
 *
 * This element can be used to display custom content in a normal element space.
 * The content will be put inside of a <span> tag. Use a container element if
 * you need a larger block element (it's put inside of a <div> tag).
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Freeform extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $content; ///< string the content

	/**
	 * Constructor
	 *
	 * @param string $name     element name
	 * @param string $label    the label
	 * @param string $content  the content
	 * @param string $id       optional ID
	 */
	public function __construct($name, $label, $content, $id = null) {
		parent::__construct($name, $label, '', $id);
		$this->setContent($content);
	}

	/**
	 * Sets the content
	 *
	 * @param string $content  the new content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('form/freeform.phtml');
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
	 * the container's content (as an MD5 hash).
	 *
	 * @return string  the hashed content
	 */
	public function getDisplayValue() {
		return md5($this->content);
	}
}
