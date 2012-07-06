<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Response_Stream extends sly_Response {
	protected $file;

	/**
	 * Constructor
	 *
	 * @param string  $file     Full path to the file that should be streamed
	 * @param integer $status   The response status code
	 * @param array   $headers  An array of response headers
	 */
	public function __construct($file, $status = 200, array $headers = array()) {
		$path = realpath($file);

		if ($path === false || !is_file($path)) {
			throw new sly_Exception('Could not find file "'.$file.'".');
		}

		parent::__construct(null, $status, $headers);
		$this->file = $path;
	}

	public function sendContent() {
		$fp = fopen($this->file, 'rb');

		while (!feof($fp)) {
			print fread($fp, 8192);
			ob_flush();
			flush();
		}

		fclose($fp);
	}

	public function setContent($content) {
		if (null !== $content) {
			throw new LogicException('The content cannot be set on a sly_Response_Stream instance.');
		}
	}

	public function getContent() {
		return false;
	}
}
