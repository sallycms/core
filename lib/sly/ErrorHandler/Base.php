<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_ErrorHandler_Base {
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

	public function uninit() {
		restore_exception_handler();
		restore_error_handler();
	}

	/**
	 * @param  string $file
	 * @return string
	 */
	protected function getRelativeFilename($file) {
		if (strpos($file, "eval()'d code")) {
			return "eval()'d code";
		}

		$base = SLY_BASE;

		if (strlen($file) >= strlen($base) && substr($file, 0, strlen($base)) === $base) {
			return '/'.str_replace("\\", '/', substr($file, strlen($base) + 1)); // +1 schneidet den (Back)Slash ab
		}

		return $file;
	}
}
