<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ErrorHandler_ErrorException extends ErrorException {
	protected $trace;

	// E_ERROR | E_PARSE | E_USER_ERROR
	const SEVERE = 261;

	public function __construct($severity, $message, $file, $line, array $trace = null) {
		parent::__construct($message, 0, $severity, $file, $line);

		$this->trace = $trace;
	}

	public function getName() {
		return sly_ErrorHandler_Base::$codes[$this->getSeverity()];
	}

	public function getRealTrace() {
		return $this->trace;
	}

	public function setTrace(array $trace) {
		$this->trace = $trace;
	}

	public function isSevere() {
		return $this->getSeverity() & self::SEVERE;
	}
}
