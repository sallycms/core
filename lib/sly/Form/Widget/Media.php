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
 * Media widget
 *
 * This element will render a special widget that allows the user to select
 * a file from the mediapool. The handled value is the file's name, not its ID.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_Media extends sly_Form_Widget_MediaBase implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param string $value  the current value (a filename)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->addOuterClass('sly-form-mediawidget-row');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('element/widget/media.phtml');
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

	public static function getFullName($filename) {
		$medium = sly_Util_Medium::findByFilename($filename);
		$value  = '';

		if ($medium) {
			$title = $medium->getTitle();
			$value = $title ? $title : $filename;

			if (mb_strlen($title) > 0) {
				$value .= " ($filename)";
			}
		}

		return $value;
	}
}
