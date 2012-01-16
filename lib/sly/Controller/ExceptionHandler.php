<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @since 0.6
 */
interface sly_Controller_ExceptionHandler {
	/**
	 * Check if the exception can be handled
	 *
	 * @param  Exception $ex  the caught exception
	 * @return boolean        true if the handler should be called, else false
	 */
	public function handlesException(Exception $ex);

	/**
	 * Handle exception
	 *
	 * This method is called when an exception happens during execution of the
	 * current controllers action.
	 *
	 * @param  Exception $ex      the caught exception
	 * @param  string    $action  the called action
	 * @return mixed              anything the app recognizes as a controller
	 *                            return value. In most cases, you can either
	 *                            return a sly_Response instance or print out
	 *                            some content (returning nothing).
	 */
	public function handleException(Exception $ex, $action);
}
