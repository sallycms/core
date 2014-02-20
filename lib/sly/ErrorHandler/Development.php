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
class sly_ErrorHandler_Development extends sly_ErrorHandler_Base {
	protected $output = array();

	/**
	 * Initialize error handler
	 *
	 * This method sets the error level and makes PHP display all errors. If
	 * logging has been confirmed (log_errors), this will continue to work.
	 */
	public function init() {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		ini_set('display_errors', 'On');
		set_error_handler(array($this, 'onCaughtError'));
		register_shutdown_function(array($this, 'onShutdown'));
		$this->runShutdown = true;
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
	 * Collects the string representation for all severe exception; does nothing
	 * with non-severe exceptions.
	 */
	public function handleException(Exception $e) {
		// due to display_errors=On, non-Exceptions have already been printed;
		// for real Exceptions we have to do it later in the aaaauuuggghhhh handler
		if ($this->isSevere($e)) {
			$this->output[] = trim((string) $e);
		}
	}

	/**
	 * Handle script death
	 *
	 * This method is the last one that is called when a script dies away and is
	 * responsible for displaying the error page and sending the HTTP500 header.
	 *
	 * @param Exception $e  the error that caused the script to die
	 */
	protected function aaaauuuggghhhh(Exception $e) {
		while (ob_get_level()) ob_end_clean();
		ob_start('ob_gzhandler');

		// Exceptions are never rendered with special HTML, even with
		// html_errors=On; so it's perfectly fine to serve them as text/plain
		// (also prevents XSS attacks). Plus it's more readable. Everyone wins!

		header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
		header('Content-Type: text/plain; charset=UTF-8');
		header('Expires: Fri, 30 Oct 1998 14:19:41 GMT');

		print implode("\n----------\n", $this->output);
	}
}
