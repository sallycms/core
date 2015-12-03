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
 * @since 0.7
 */
interface sly_Controller_Generic extends sly_Controller_Interface {
	/**
	 * Perform generic action
	 *
	 * @param  string $action  the action to be called
	 * @return mixed           a Response instance or null if the action's output shall be captured
	 */
	public function genericAction($action);
}
