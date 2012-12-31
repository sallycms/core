<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_FactoryTest extends PHPUnit_Framework_TestCase {
	public function testGetService() {
		$service = sly_Service_Factory::getAddOnPackageService();
		$this->assertInstanceOf('sly_Service_Package', $service);
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testGetMissingService() {
		sly_Service_Factory::getService('FooBar'.uniqid());
	}

	public function testGetSingleton() {
		$a = sly_Service_Factory::getAddOnPackageService();
		$b = sly_Service_Factory::getAddOnPackageService();
		$this->assertSame($a, $b);

		$b = sly_Core::getContainer()->getAddOnPackageService();
		$this->assertSame($a, $b);
	}
}
