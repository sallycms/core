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
 * @ingroup layout
 */
class sly_Layout_XHTML5 extends sly_Layout_XHTML {
	protected $manifest; ///< string

	/**
	 * @param string $manifest
	 */
	public function setManifest($manifest) {
		$this->manifest = $manifest;
	}

	/**
	 * @param boolean $isTransitional  true or false, it's your choice
	 */
	public function setTransitional($isTransitional = true) {
		trigger_error('Cannot set transitional on XHTML5 layout.', E_USER_NOTICE);
	}

	public function printHeader() {
		$this->renderView('layout/xhtml5/head.phtml');
	}
}
