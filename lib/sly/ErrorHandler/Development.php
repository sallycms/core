<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author Christoph
 * @since  0.5
 */
class sly_ErrorHandler_Development extends sly_ErrorHandler_Base implements sly_ErrorHandler {
	/**
	 * Initialize error handler
	 *
	 * This method sets the error level and makes PHP display all errors. If
	 * logging has been confirmed (log_errors), this will continue to work.
	 */
	public function init() {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		ini_set('display_errors', 'On');
	}

	/**
	 * Un-initialize the error handler
	 *
	 * Since this error handler doesn't actually catch and handle errors, this
	 * method does nothing special.
	 */
	public function uninit() {
		/* do nothing */
	}

	/**
	 * Handle a catched exception
	 *
	 * This method will just print the exception.
	 */
	public function handleException(Exception $e) {
		print $e;
		die;
	}
}
