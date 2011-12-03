<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_ArticleTypeTest extends PHPUnit_Framework_TestCase {
	private function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getArticleTypeService();
		return $service;
	}

	public function testGetArticleTypes() {
		$types    = $this->getService()->getArticleTypes();
		$expected = array('default' => 'Standard', 'special' => 'Special');

		$this->assertCount(2, $types);
		$this->assertEquals($expected, $types);
	}

	public function testGetTitle() {
		$title = $this->getService()->getTitle('default');
		$this->assertEquals('Standard', $title);
	}

	public function testGetTemplate() {
		$title = $this->getService()->getTemplate('default');
		$this->assertEquals('standard', $title);
	}

	public function testGet() {
		$title = $this->getService()->get('default', 'title');
		$this->assertEquals('Standard', $title);
	}

	public function testGetCustomProperty() {
		$val = $this->getService()->get('special', 'testprop');
		$this->assertEquals(array(1, 2), $val);
	}

	public function testGetNonExistingProperty() {
		$val = $this->getService()->get('default', 'nonexisting', 'fallback');
		$this->assertEquals('fallback', $val);
	}

	public function testExists() {
		$this->assertTrue($this->getService()->exists('default'));
		$this->assertFalse($this->getService()->exists('nonexisting'));
	}

	/**
	 * @depends           testExists
	 * @expectedException sly_Exception
	 */
	public function testExistsException() {
		$this->getService()->exists('nonexisting', true);
	}
}
