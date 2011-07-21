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
 * Base ajax controller
 *
 * This class should be used for controllers that are supposed to be called
 * via AJAX calls. It will clear all output buffers, open a fresh one and die
 * away when done dispatching.
 *
 * @ingroup controller
 * @author  Zozi
 * @since   0.1
 */
abstract class sly_Controller_Ajax extends sly_Controller_Base {
	/**
	 * Initialize controller
	 *
	 * Clears all output buffers and opens a new, gzipped one.
	 */
	protected function init() {
		while (ob_get_level()) ob_end_clean();
		ob_start('ob_gzhandler');
	}

	/**
	 * Finishes the dispatching
	 *
	 * This method will print the output buffer (if still open) and exit the
	 * script execution.
	 */
	protected function teardown() {
		while (ob_get_level()) ob_end_flush();
		exit();
	}
	
	protected function getViewFolder() {
		return false;
	}
}
