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
 * @author Christoph
 * @since  0.5
 */
class sly_ErrorHandler_Development extends sly_ErrorHandler_Base implements sly_ErrorHandler {
	protected $runShutdown = true; ///< bool  if true, the shutdown function will be executed

	public function init() {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		ini_set('display_errors', 'On');

		// PHP >= 5.2
		if (function_exists('error_get_last')) {
			register_shutdown_function(array($this, 'shutdownFunction'));
		}
	}

	public function uninit() {
		$this->runShutdown = false;
	}

	/**
	 * Shutdown-Hook
	 *
	 * Diese Methode prüft am Ende eines jeden Scripts, ob ein zum Abbruch
	 * führender Fehler aufgetreten ist. Wenn ja, wird für ihn eine entsprechende
	 * Fehlerseite (im symfony-Stil) angezeigt. Ist kein Fehler aufgetreten,
	 * passiert nichts.
	 */
	public function shutdownFunction() {
		if ($this->runShutdown) {
			$e = error_get_last();

			if (is_array($e) && $e['type'] & (E_ERROR | E_PARSE)) {
				$this->noBacktrace = true;
				$this->handleError($e['type'], $e['message'], $e['file'], $e['line'], null);
				$this->noBacktrace = false;
			}
		}
	}

	/**
	 * Fehler behandeln
	 *
	 * Diese Funktion wird von PHP aufgerufen, wenn ein Fehler aufgetreten ist.
	 *
	 * @param int    $severity  der Fehlercode
	 * @param string $message   die Fehlermeldung
	 * @param string $file      die Datei, in der der Fehler auftrat
	 * @param int    $line      die Zeilennummer
	 * @param mixed  $context   der aktuelle Kontext (?) (wird nicht ausgewertet)
	 */
	public function handleError($severity, $message, $file, $line, array $context) {
		$errorLevel = error_reporting();
		if (!($severity & $errorLevel)) return;
		$this->handleProblem(self::$codes[$severity], $message, $file, $line, null, $severity);
	}

	/**
	 * Exception behandeln
	 *
	 * Diese Funktion wird von PHP aufgerufen, wenn eine ungefangene Exception
	 * auftrat.
	 *
	 * @param Exception $exception  die aufgetretene Exception
	 */
	public function handleException(Exception $exception) {
		$this->handleProblem(get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace(), -1);
	}
}
