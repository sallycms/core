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
class sly_Form_Select_DropDown extends sly_Form_Select_Base implements sly_Form_IElement {
	public function setSize($size) {
		$this->setAttribute('size', $size);
	}

	public function render() {
		return $this->renderFilename('form/select/dropdown.phtml');
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-select');
		return $this->outerClass;
	}
}
