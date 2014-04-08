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
 * Simple text
 *
 * This form elements displays the given text without any real form element.
 * Use this for longer help texts or read-only information.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Text extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $content;  ///< string
	protected $isHTML;   ///< boolean

	/**
	 * Constructor
	 *
	 * @param string  $label   the label
	 * @param string  $text    the content
	 * @param string  $id      optional ID
	 * @param boolean $isHTML  whether or not the $text should be treated as HTML
	 */
	public function __construct($label, $text, $id = null, $isHTML = false) {
		$id = $id === null ? 'a'.uniqid() : $id;
		parent::__construct('', $label, '', $id);
		$this->content = $text;
		$this->isHTML  = !!$isHTML;

		$this->addOuterClass('sly-form-read-row');
		$this->addClass('sly-form-read');
	}

	/**
	 * Sets the text
	 *
	 * @param  string $text   the new text
	 * @return sly_Form_Text  the object itself
	 */
	public function setText($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 * Renders the element
	 *
	 * This method renders the text in a single <span> tag.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$content = $this->isHTML ? $this->content : nl2br(sly_html($this->content));
		return '<span '.$this->getAttributeString().'>'.$content.'</span>';
	}

	/**
	 * Controls the XHTML conversion
	 *
	 * @param  boolean $isHTML  set this to false to disabled the sly_html() in render()
	 * @return sly_Form_Text    the object itself
	 */
	public function setIsHTML($isHTML) {
		$this->isHTML = (boolean) $isHTML;
		return $this;
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
