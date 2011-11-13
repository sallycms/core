<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_LanguageTest extends sly_DatabaseTest {
	protected function getDataSetName() {
		return 'sally-demopage';
	}

	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getLanguageService();
		return $service;
	}

	public function testAdd() {
		$service = $this->getService();
		$lang    = $service->create(array('name' => 'test', 'locale' => 'xx_YY'));

		$this->assertInstanceOf('sly_Model_Language', $lang);
		$this->assertEquals('test', $lang->getName());
		$this->assertEquals('xx_YY', $lang->getLocale());

		$langs = sly_Util_Language::findAll();
		$this->assertEquals(3, count($langs));
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service = $this->getService();
		$lang    = $service->create(array('name' => 'test', 'locale' => 'xx_YY'));

		$lang->setLocale('yy_XX');
		$service->save($lang);

		$lang = $service->findById($lang->getId());
		$this->assertEquals('yy_XX', $lang->getLocale());
	}

	/**
	 * @depends testAdd
	 */
	public function testDelete() {
		$service = $this->getService();
		$lang    = $service->create(array('name' => 'test', 'locale' => 'xx_YY'));

		$service->delete(array('id' => $lang->getId()));
		$this->assertNull($service->findById($lang->getId()));

		$langs = sly_Util_Language::findAll();
		$this->assertEquals(2, count($langs));
	}
}
