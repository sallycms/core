<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_FactoryTest extends PHPUnit_Framework_TestCase {
	public function testGetService() {
		$service = sly_Service_Factory::getComponentService();
		$this->assertInstanceOf('sly_Service_Component', $service);
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testGetMissingService() {
		sly_Service_Factory::getService('FooBar'.uniqid());
	}

	public function testGetSingleton() {
		$a = sly_Service_Factory::getComponentService();
		$b = sly_Service_Factory::getService('Component');
		$this->assertSame($a, $b);
	}
}
