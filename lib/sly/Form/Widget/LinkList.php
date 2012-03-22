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
 * Linklist widget
 *
 * This element will render a special widget that allows the user to select
 * a list of articles. The articles will be returned without any language
 * information, so only their IDs are returned.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_LinkList extends sly_Form_Widget_LinkBase implements sly_Form_IElement {
	protected $min = 0;
	protected $max = -1;

	public function setMinElements($min) {
		$min = (int) $min;
		$this->min = $min <= 0 ? 0 : $min;

		if ($this->min > $this->max) {
			$this->max = $this->min;
		}
	}

	public function setMaxElements($max) {
		$max = (int) $max;
		$this->max = $max < 0 ? -1 : $max;

		if ($this->min < $this->max) {
			$this->min = $this->max;
		}
	}

	public function getMinElements() {
		return $this->min;
	}

	public function getMaxElements() {
		return $this->max;
	}

	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param mixed  $value  the current value (array or comma seperated string of IDs)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->addOuterClass('sly-form-linklistwidget-row');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('element/widget/linklist.phtml');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return array  list of article IDs
	 */
	public function getDisplayValue() {
		$ids = $this->getDisplayValueHelper('string', false);
		if ($ids === null) return array();
		return is_array($ids) ? $ids : explode(',', $ids);
	}
}
