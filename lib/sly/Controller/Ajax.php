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
 * @ingroup controller
 */
abstract class sly_Controller_Ajax extends sly_Controller_Base {

	protected function init() {
		//cleanup before dispatching
		while(ob_get_level()) ob_end_clean();
	}

	protected function teardown() {
		//exit, our output should be clean
		exit();
	}
}
