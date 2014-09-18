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
 * Generic container
 *
 * This class wraps an empty container that can be used to display any text
 * or content in the form. The content will be printed as-is (so it will not
 * be XHTML encoded!).
 *
 * Use this if you have complex form elements that cannot be displayed by using
 * the normal form elements.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Container extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $content; ///< string  the content

	/**
	 * Constructor
	 *
	 * @param string $id       optional ID
	 * @param string $class    optional CSS class
	 * @param string $style    optional inline CSS code
	 * @param string $content  the container's content
	 */
	public function __construct($id = null, $class = '', $style = '', $content = null) {
		parent::__construct('', '', '', $id);

		$this->setAttribute('class', $class);
		$this->setAttribute('style', $style);
		$this->setContent($content);
	}

	/**
	 * Sets the content
	 *
	 * @param  string $content     the new content
	 * @return sly_Form_Container  the object itself
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('element/container.phtml');
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
	 * the container's content (as an MD5 hash).
	 *
	 * @return string  the hashed content
	 */
	public function getDisplayValue() {
		return md5($this->content);
	}
}
