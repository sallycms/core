<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	 * @param mixed   $file     Full path to the file that should be streamed or stream ressource
	 * @param integer $status   The response status code
	 * @param array   $headers  An array of response headers
	 */
	public function __construct($file, $status = 200, array $headers = array()) {
		parent::__construct(null, $status, $headers);

		if (is_resource($file)) {
			if (get_resource_type ($file) == 'stream') {
				$this->file = $file;
			}
			else {
				throw new sly_Exception('Ressource must be of type stream');
			}
		}
		else {
			$path = realpath($file);

			if ($path === false || !is_file($path)) {
				throw new sly_Exception('Could not find file "'.$file.'".');
			}

			$this->file = $path;
		}
	}

	public function send(sly_Request $request = null, sly_Event_IDispatcher $dispatcher = null) {
		// make sure there is no output buffer blocking our chunked response
		while (@ob_end_clean());

		parent::send($request, $dispatcher);
	}

	public function sendContent() {
		$fp = is_resource($this->file) ? $this->file : fopen($this->file, 'rb');

		while (!feof($fp)) {
			print fread($fp, 16384); // send 16K at once
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
