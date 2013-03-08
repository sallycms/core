<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class DeletedArticleTest extends sly_BaseTest {

	protected static $clang = 5;

	protected function getDataSetName() {
		return 'sally-demopage';
	}

	protected function getArticleService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getArticleService();
		return $service;
	}

	protected function getCategoryService() {
		static $cservice = null;
		if (!$cservice) $cservice = sly_Service_Factory::getCategoryService();
		return $cservice;
	}

	protected function getDeletedArticleService() {
		static $dservice = null;
		if (!$dservice) $dservice = sly_Core::getContainer()->getDeletedArticleService();
		return $dservice;
	}

	public function testRestore() {
		$service  = $this->getArticleService();
		$dservice = $this->getDeletedArticleService();

		$service->deleteById(8);
		$deleted = $dservice->find(array('id' => 8));
		foreach($deleted as $x) {
			$this->assertTrue($x->isDeleted());
		}

		$dservice->restore(8);
		$this->assertInstanceOf('sly_Model_Article', $service->findByPK(8, self::$clang));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testRestoreMissing() {
		$dservice = $this->getDeletedArticleService();
		$dservice->restore(1);
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testRestoreCategoryMissing() {
		$service  = $this->getArticleService();
		$cservice = $this->getCategoryService();
		$dservice = $this->getDeletedArticleService();
		$newId    = $service->add(2, 'Test', 1);

		$service->deleteById($newId);
		$cservice->deleteById(2);

		// delte new article
		$dservice->restore($newId);
	}

}
