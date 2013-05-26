<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	protected function getRequiredAddOns() {
		return array();
	}

	protected function loadAddOn($addon) {
		$service = sly_Core::getContainer()->getAddOnManagerService();
		$service->load($addon, true, sly_Core::getContainer());
	}
}
