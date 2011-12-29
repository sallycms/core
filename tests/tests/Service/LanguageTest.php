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
		$this->assertTrue(sly_Util_Language::exists($lang->getId()));
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
		$service  = $this->getService();
		$aService = sly_Service_Factory::getArticleService();
		$articles = count($aService->find());
		$lang     = $service->create(array('name' => 'test', 'locale' => 'xx_YY'));
		$id       = $lang->getId();

		$service->delete(array('id' => $id));
		$this->assertNull($service->findById($id));

		$langs = sly_Util_Language::findAll();
		$this->assertEquals(2, count($langs));
		$this->assertFalse(sly_Util_Language::exists($id));

		$this->assertCount($articles, $aService->find());
	}

	/**
	 * @depends testAdd
	 */
	public function testDuplicatesArticles() {
		$service  = $this->getService();
		$aService = sly_Service_Factory::getArticleService();
		$articles = count($aService->find()) / 2; // there are two languages in sally-demopage
		$lang     = $service->create(array('name' => 'test', 'locale' => 'xx_YY'));
		$id       = $lang->getId();

		$this->assertCount(3*$articles, $aService->find());
		$this->assertInstanceOf('sly_Model_Article', $aService->findById(6, $id));
	}
}
