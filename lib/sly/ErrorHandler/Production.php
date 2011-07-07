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
 * Basic error handler for pages in production
 *
 * Logs all severe errors (no strict and deprecated ones) via sly_Log and
 * prints out a neutral error message when something went really wrong (like an
 * uncaught exception).
 *
 * @author Christoph
 * @since  0.5
 */
class sly_ErrorHandler_Production extends sly_ErrorHandler_Base implements sly_ErrorHandler {
	protected $runShutdown = true;  ///< bool     if true, the shutdown function will be executed
	protected $log         = null;  ///< sly_Log  logger instance

	const MAX_LOGFILE_SIZE = 1048576; ///< int  max filesize before rotation starts (1 MB)
	const MAX_LOGFILES     = 10;      ///< int  max number of rotated logfiles to keep

	/**
	 * Initialize error handler
	 *
	 * This method sets the error level, disables all error handling by PHP and
	 * registered itself as the new error and exception handler.
	 */
	public function init() {
		error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
		ini_set('display_errors', 'Off');
		ini_set('log_errors', 'Off');
		ini_set('html_errors', 'Off');

		set_exception_handler(array($this, 'handleException'));
		set_error_handler(array($this, 'handleError'));

		// PHP >= 5.2
		if (function_exists('error_get_last')) {
			register_shutdown_function(array($this, 'shutdownFunction'));
		}

		// Init the sly_Log instance so we don't fail when loading classes result
		// in errors like E_STRICT. See PHP Bug #54054 for details.
		$this->log = sly_Log::getInstance('errors');
		$this->log->setFormat('[%date% %time%] %message%');
	}

	/**
	 * Un-initialize the error handler
	 *
	 * Call this if you don't want the error handling anymore.
	 */
	public function uninit() {
		parent::uninit();
		$this->runShutdown = false;
	}

	/**
	 * Handle regular PHP errors
	 *
	 * This method is called when a notice, warning or error happened. It will
	 * log the error, but not print it.
	 *
	 * @param int    $severity
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 * @param array  $context
	 */
	public function handleError($severity, $message, $file, $line, array $context) {
		$errorLevel = error_reporting();

		// only perform special handling when required
		if ($severity & $errorLevel) {
			$this->handleProblem(self::$codes[$severity], $message, $file, $line, $severity);
		}

		// always die away if the problem was *really* bad
		if ($severity & (E_ERROR | E_PARSE | E_USER_ERROR)) {
			$this->aaaauuuggghhhh(array('type' => $severity, 'message' => $message, 'file' => $file, 'line' => $line));
		}
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
	public function handleException(Exception $exception) {
		// perform normal error handling (logging)
		$this->handleProblem(get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getCode());

		// always die away if *really* severe
		$this->aaaauuuggghhhh($exception);
	}

	/**
	 * Handle all errors
	 *
	 * This method is called for both exceptions and errors and performs the
	 * actual logging.
	 *
	 * @param string $errorName
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 * @param int    $errorCode
	 */
	protected function handleProblem($errorName, $message, $file, $line, $errorCode = -1) {
		$file    = $this->getRelativeFilename($file);
		$message = trim($message);

		// doesn't really matter what method we call since we use our own format
		$this->log->error("PHP $errorName ($errorCode): $message in $file line $line [$_SERVER[REQUEST_METHOD] $_SERVER[REQUEST_URI]]");
	}

	/**
	 * Shutdown function
	 *
	 * This method is called when the scripts exits. It checks for unhandled
	 * errors and calls the regular error handling when necessarry.
	 *
	 * Call uninit() if you do not want this function to perform anything.
	 */
	public function shutdownFunction() {
		if ($this->runShutdown) {
			$e = error_get_last();

			// run regular error handling when there's an error
			if (isset($e['type'])) {
				$this->handleError($e['type'], $e['message'], $e['file'], $e['line'], null);
			}
		}
	}

	/**
	 * Handling script death
	 *
	 * This method is the last one that is called when a script dies away and is
	 * responsible for displaying the error page and sending the HTTP500 header.
	 *
	 * @param mixed $error  the error that caused the script to die (array or Exception)
	 */
	protected function aaaauuuggghhhh($error) {
		while (ob_get_level()) ob_end_clean();
		header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');

		$errorpage = SLY_DEVELOPFOLDER.'/error.phtml';

		if (file_exists($errorpage)) {
			include $errorpage;
		}
		else {
			header('Content-Type: text/plain; charset=UTF-8');
			print 'Es ist ein interner Fehler aufgetreten.'."\n".'Bitte versuchen Sie es später noch einmal.';
		}

		die;
	}
}
