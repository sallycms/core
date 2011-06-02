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
class sly_Form_Widget_MediaButton extends sly_Form_Widget implements sly_Form_IElement {
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id, 'mediabutton');
		$this->setAttribute('class', 'rex-form-text');
	}

	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/mediabutton.phtml');
	}

	public function getID() {
		return 'REX_MEDIA_'.$this->getWidgetID();
	}

	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}
}
