<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
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
	protected $log = null;  ///< sly_Log  logger instance

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

		set_exception_handler(array($this, 'onCaughtException'));
		set_error_handler(array($this, 'onCaughtError'));
		register_shutdown_function(array($this, 'onShutdown'));
		$this->runShutdown = true;

		// Init the sly_Log instance so we don't fail when loading classes result
		// in errors like E_STRICT. See PHP Bug #54054 for details.
		$this->log = sly_Log::getInstance('errors');
		$this->log->setFormat('[%date% %time%] %message%');
		$this->log->enableRotation(self::MAX_LOGFILE_SIZE, self::MAX_LOGFILES);

		return true;
	}

	/**
	 * Un-initialize the error handler
	 *
	 * Call this if you don't want the error handling anymore.
	 */
	public function uninit() {
		restore_exception_handler();
		restore_error_handler();

		$this->runShutdown = false;
	}

	/**
	 * Handle uncaught exceptions
	 *
	 * This method is called when an exception is thrown, but not caught. It will
	 * log the exception and stop the script execution by displaying a neutral
	 * error page.
	 *
	 * @param Exception $e
	 */
	public function handleException(Exception $e) {
		if (isset($_SERVER['REQUEST_METHOD'])) {
			$req = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
		}
		else {
			$req = 'php:'.$_SERVER['PHP_SELF'];
		}

		// doesn't really matter what method we call since we use our own format
		$this->log->error(sprintf('%s (%d): %s in %s line %d [%s]',
			$e instanceof sly_ErrorHandler_ErrorException ? 'PHP '.$e->getName() : get_class($e),
			$e->getCode(),
			trim($e->getMessage()),
			$this->getRelativeFilename($e->getFile()),
			$e->getLine(),
			$req
		));
	}

	/**
	 * Handling script death
	 *
	 * This method is the last one that is called when a script dies away and is
	 * responsible for displaying the error page and sending the HTTP500 header.
	 *
	 * @param Exception $e  the error that caused the script to die
	 */
	protected function aaaauuuggghhhh(Exception $e) {
		while (ob_get_level()) ob_end_clean();
		ob_start('ob_gzhandler');

		header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: private, no-cache');
		header('Expires: Fri, 30 Oct 1998 14:19:41 GMT');

		$errorpage = SLY_DEVELOPFOLDER.'/error.phtml';

		if (!file_exists($errorpage)) {
			$errorpage = SLY_COREFOLDER.'/views/error.phtml';
		}

		include $errorpage;
	}

	/**
	 * @return sly_Log  the current log instance
	 */
	public function getLog() {
		return $this->log;
	}
}
