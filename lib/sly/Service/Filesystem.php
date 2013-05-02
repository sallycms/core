<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\FilesystemMap;
use Gaufrette\StreamWrapper;

/**
 * Filesystem Service
 */
class sly_Service_Filesystem {
	protected $temp;

	public function __construct($tempDir) {
		$this->temp = $tempDir;
	}

	public function getTempDirectory() {
		return $this->temp;
	}

	public function registerStreamWrapper(FilesystemMap $fsMap) {
		StreamWrapper::setFilesystemMap($fsMap);
		StreamWrapper::register();
	}
}
