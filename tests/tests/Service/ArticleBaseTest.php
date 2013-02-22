<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_ArticleBaseTest extends sly_Service_ArticleTestBase {
	private static $clang = 5;

	public static function setUpBeforeClass() {
		sly_Core::setCurrentClang(self::$clang);
	}

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	public function testGetNonExisting() {
		$this->assertNull($this->getService()->findByPK(1, self::$clang));
		$this->assertNull($this->getService()->findByPK(1, 2));
	}

	public function testAdd() {
		$service = $this->getService();
		$newID   = $service->add(0, 'my "article"', 1, -1);

		$this->assertInternalType('int', $newID);

		$art = $service->findByPK($newID, self::$clang);
		$this->assertInstanceOf('sly_Model_Article', $art);

		$this->assertEquals('my "article"', $art->getName());
		$this->assertEquals('', $art->getCatName());
		$this->assertEquals(1, $art->getPosition());
		$this->assertEquals('|', $art->getPath());
		$this->assertEquals(0, $art->getParentId());
		$this->assertTrue($art->isOnline());
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service = $this->getService();
		$id      = $service->add(0, 'my article', 1, -1);
		$art     = $service->findByPK($id, self::$clang);

		$service->edit($art, 'new title', 0);

		$art     = $service->findByPK($id, self::$clang);

		$this->assertEquals('new title', $art->getName());
		$this->assertEquals('', $art->getCatName());
	}

	/**
	 * @depends testAdd
	 */
	public function testDelete() {
		$service = $this->getService();
		$user    = sly_Service_Factory::getUserService()->findById(1);
		$new     = $service->add(0, 'Test', 1, -1, $user);

		// add a nw revision
		$article = $service->findByPK($new, self::$clang);
		$service->touch($article, $user);

		$service->deleteById($new);
		$this->assertFalse($service->exists($new));

		$article = $service->findByPK($new, self::$clang);
		$this->assertNull($article);
	}

	/**
	 * @depends testAdd
	 */
	public function testTouch() {
		$service = $this->getService();
		$id      = $service->add(0, 'my article', 1, -1);
		$article = $service->findByPK($id, self::$clang);
		$user    = sly_Service_Factory::getUserService()->findById(1);

		$articleNewRevision = $service->touch($article, $user);

		$this->assertGreaterThanOrEqual($article->getCreateDate(), $articleNewRevision->getCreateDate());
		$this->assertEquals($user->getLogin(), $articleNewRevision->getUpdateUser());
		$this->assertEquals(1, $articleNewRevision->getRevision(), 'Touch should increase revision');
	}
}
