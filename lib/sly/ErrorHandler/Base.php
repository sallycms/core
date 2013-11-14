<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_ErrorHandler_Base implements sly_ErrorHandler {
	protected $runShutdown;

	// E_ERROR | E_PARSE | E_USER_ERROR
	protected static $severe = 261;

	public static $levelMapping = array(
		E_ERROR             => E_ERROR,
		E_WARNING           => E_WARNING,
		E_NOTICE            => E_NOTICE,
		E_STRICT            => E_STRICT,
		E_PARSE             => E_PARSE,
		E_RECOVERABLE_ERROR => E_ERROR,
		E_DEPRECATED        => E_DEPRECATED,
		E_USER_ERROR        => E_ERROR,
		E_USER_WARNING      => E_WARNING,
		E_USER_NOTICE       => E_NOTICE,
		E_USER_DEPRECATED   => E_DEPRECATED,
		E_COMPILE_ERROR     => E_ERROR
	); ///< array

	public static $codes = array(
		E_ERROR             => 'Error',
		E_WARNING           => 'Warning',
		E_NOTICE            => 'Notice',
		E_STRICT            => 'Strict',
		E_PARSE             => 'Parse Error',
		E_RECOVERABLE_ERROR => 'Recoverable Error',
		E_DEPRECATED        => 'Deprecated',
		E_USER_ERROR        => 'User Error',
		E_USER_WARNING      => 'User Warning',
		E_USER_NOTICE       => 'User Notice',
		E_USER_DEPRECATED   => 'User Deprecated',
		E_COMPILE_ERROR     => 'Compile Error'
	); ///< array

	/**
	 * @param  string $file
	 * @return string
	 */
	protected function getRelativeFilename($file) {
		return sly_Util_File::getRelativeFilename($file);
	}

	/**
	 * @param int    $severity
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 * @param mixed  $context
	 */
	public function onCaughtError($severity, $message, $file, $line, array $context = null) {
		$errorLevel = error_reporting();
		if (!($severity & $errorLevel)) return;

		$trace = array_slice(debug_backtrace(), 1);
		$error = new sly_ErrorHandler_ErrorException($severity, $message, $file, $line, $trace);

		$this->onCaughtException($error);
	}

	/**
	 * Handle uncaught exceptions
	 *
	 * This method is called when an exception is thrown, but not caught. It will
	 * log the exception and stop the script execution by displaying a neutral
	 * error page.
	 *
	 * @param Exception $exception
	 */
	public function onCaughtException(Exception $exception) {
		// perform normal error handling (logging)
		$this->handleException($exception);

		// always die away if *really* severe
		if ($this->isSevere($exception)) {
			$this->aaaauuuggghhhh($exception);
			exit(1);
		}
	}

	/**
	 * Shutdown function
	 *
	 * This method is called when the scripts exits. It checks for unhandled
	 * errors and calls the regular error handling when necessarry.
	 *
	 * Call uninit() if you do not want this function to perform anything.
	 */
	public function onShutdown() {
		if ($this->runShutdown) {
			$e = error_get_last();

			// run regular error handling when there's an error
			if (isset($e['type'])) {
				$this->onCaughtError($e['type'], $e['message'], $e['file'], $e['line'], null);
			}
		}
	}

	/**
	 * Determine if an exception is severe
	 *
	 * @param  Exception $e
	 * @return boolean
	 */
	protected function isSevere(Exception $e) {
		return (!($e instanceof sly_ErrorHandler_ErrorException)) || $e->isSevere();
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
		/* do nothing */
	}
}
