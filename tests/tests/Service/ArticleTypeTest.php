<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
		$expected = array('default' => 'Standard', 'special' => 'Special', 'test' => 'Test');

		$this->assertCount(3, $types);
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

	/**
	 * @dataProvider getModulesProvider
	 */
	public function testGetModules($type, $slot, $expected) {
		$expected = $expected === array() ? array(): array_combine($expected, $expected); // build key=>title list
		$modules  = $this->getService()->getModules($type, $slot);

		$this->assertEquals($expected, $modules);
	}

	public function getModulesProvider() {
		return array(
			array('default', 'main',   array('test1', 'test2')),
			array('default', 'test',   array('test2')),
			array('special', null,     array('test1', 'test2')),
			array('special', 'main',   array('test1', 'test2')),
			array('special', 'test',   array('test1', 'test2')),
			array('special', 'noslot', array()),
			array('test',    null,     array('test1')),
			array('test',    'main',   array('test1'))
		);
	}

	/**
	 * @dataProvider hasModuleProvider
	 * @depends      testGetModules
	 */
	public function testHasModule($type, $slot, $module, $expected) {
		$result = $this->getService()->hasModule($type, $module, $slot);
		$this->assertEquals($expected, $result);
	}

	public function hasModuleProvider() {
		return array(
			array('default', 'main',   'test1', true),
			array('default', 'main',   'test2', true),
			array('default', 'test',   'test1', false),
			array('default', 'test',   'test2', true),
			array('special', 'noslot', 'test1', false),
			array('special', 'noslot', 'nonexistingmodule', false)
		);
	}
}
