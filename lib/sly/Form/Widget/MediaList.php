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
 * Medialist widget
 *
 * This element will render a special widget that allows the user to select
 * a list of files from the mediapool.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_MediaList extends sly_Form_Widget_MediaBase implements sly_Form_IElement {
	protected $min = 0;
	protected $max = -1;

	/**
	 * Set minimum number of required files
	 *
	 * @param int $min
	 */
	public function setMinElements($min) {
		$min = (int) $min;
		$this->min = $min <= 0 ? 0 : $min;

		if ($this->min > $this->max) {
			$this->max = $this->min;
		}
	}

	/**
	 * Set maximum number of allowed files
	 *
	 * @param int $max  (-1 means 'no limit')
	 */
	public function setMaxElements($max) {
		$max = (int) $max;
		$this->max = $max < 0 ? -1 : $max;

		if ($this->min < $this->max) {
			$this->min = $this->max;
		}
	}

	/**
	 * Get minimum number of required files
	 *
	 * @return int
	 */
	public function getMinElements() {
		return $this->min;
	}

	/**
	 * Get maximum number of allowed files
	 *
	 * @return int
	 */
	public function getMaxElements() {
		return $this->max;
	}

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
		$this->addOuterClass('sly-form-medialistwidget-row');
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
