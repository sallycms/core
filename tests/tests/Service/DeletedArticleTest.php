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

	protected function getDeletedArticleService() {
		static $dservice = null;
		if (!$dservice) $dservice = sly_Core::getContainer()->getDeletedArticleService();
		return $dservice;
	}

	public function testRestore() {
		$service  = $this->getArticleService();
		$dservice = $this->getDeletedArticleService();
		$user     = sly_Service_Factory::getUserService()->findById(1);

		$service->deleteById(8);
		$deleted = $dservice->findLatest(array('id' => 8));
		foreach($deleted as $x) {
			$this->assertTrue($x->isDeleted());
		}

		$dservice->restore(8, $user);
		$this->assertInstanceOf('sly_Model_Article', $service->findById(8, self::$clang));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testRestoreMissing() {
		$dservice = $this->getDeletedArticleService();
		$user     = sly_Service_Factory::getUserService()->findById(1);

		$dservice->restore(1, $user);
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testRestoreCategoryMissing() {
		$service  = $this->getArticleService();
		$dservice = $this->getDeletedArticleService();
		$user     = sly_Service_Factory::getUserService()->findById(1);
		$newId    = $service->add(1, 'Test', 1, 1, $user);

		// delte new article
		$service->deleteById($newId);
		$dservice->restore($newId, $user);
	}

}
