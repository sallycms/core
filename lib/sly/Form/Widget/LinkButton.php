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
 * @ingroup form
 */
class sly_Form_Widget_LinkButton extends sly_Form_Widget implements sly_Form_IElement {
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id, 'linkbutton');
		$this->setAttribute('class', 'rex-form-text');
	}

	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/linkbutton.phtml');
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-text');
		return $this->outerClass;
	}

	public function getID() {
		return 'LINK_'.$this->getWidgetID();
	}

	public function getDisplayName() {
		return $this->attributes['name'];
	}

	public function getDisplayValue() {
		return $this->getDisplayValueHelper('int', false);
	}
}
