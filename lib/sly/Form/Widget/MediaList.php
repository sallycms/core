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
 * Medialist widget
 *
 * This element will render a special widget that allows the user to select
 * a list of files from the mediapool.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_MediaList extends sly_Form_ElementBase implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param array  $value  the current value (a list of filenames)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('element/widget/medialist.phtml');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return array  a list of filenames
	 */
	public function getDisplayValue() {
		$files = $this->getDisplayValueHelper('string', false);
		if ($files === null) return array();
		return is_array($files) ? $files : explode(',', $files);
	}
}
