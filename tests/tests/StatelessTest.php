<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_StatelessTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		sly_Core::cache()->flush('sly', true);
	}

	/**
	 * @return array
	 */
	protected function getRequiredComponents() {
		return array();
	}

	protected function loadComponent($component) {
		if (is_array($component)) {
			$service = sly_Service_Factory::getPluginService();
			$service->loadPlugin($component, true);
		}
		else {
			$service = sly_Service_Factory::getAddOnService();
			$service->loadAddOn($component, true);
		}
	}
}
