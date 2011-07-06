<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_ErrorHandler {
	public function init();
	public function uninit();

	public function handleError($severity, $message, $file, $line, array $context);
	public function handleException(Exception $ex);
}
