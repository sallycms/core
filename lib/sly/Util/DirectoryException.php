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
 * @ingroup util
 */
class sly_Util_DirectoryException extends sly_Exception {
	protected $dir;

	public function __construct($dir) {
		parent::__construct('mkdir('.$dir.') failed.');
		$this->dir = $dir;
	}

	public function getDirectory() {
		return $this->dir;
	}
}
