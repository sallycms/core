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
 * Input for file uploads
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Input_File extends sly_Form_Input_Base {
	/**
	 * Constructor
	 *
	 * @param string $name   element name
	 * @param string $label  the label
	 * @param string $id     optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $id = null) {
		parent::__construct($name, $label, '', $id);
		$this->setAttribute('type', 'file');
		$this->addOuterClass('sly-form-text');
		$this->addOuterClass('sly-form-file');
	}
}
