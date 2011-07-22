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
 * Link widget
 *
 * This element will render a special widget that allows the user to select
 * one article. The article will be returned without any language information,
 * so only its ID is returned.
 * Selection will be performed in the so-called 'linkmap', a special popup for
 * browsing through the article structure.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_Link extends sly_Form_ElementBase implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param string $value  the current value (an article ID)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('class', 'rex-form-text');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('element/widget/link.phtml');
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

	public static function getFullName($articleID) {
		static $advanced = null;

		if ($advanced === null) {
			$advanced = sly_Util_User::getCurrentUser()->hasRight('advancedMode[]');
		}

		$article = sly_Util_Article::findById($articleID);
		$value   = '';

		if ($article) {
			$title = $article->getName();
			$value = $title ? $title : $filename;

			if ($advanced) {
				$value .= " [$articleID]";
			}
		}

		return $value;
	}
}
