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
	public static function setUpBeforeClass() {
		sly_Core::setCurrentClang(1);
	}

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	public function testGetNonExisting() {
		$this->assertNull($this->getService()->findById(1));
		$this->assertNull($this->getService()->findById(1), 2);
	}

	public function testAdd() {
		$service = $this->getService();
		$newID   = $service->add(0, 'my "article"', 1, -1);

		$this->assertInternalType('int', $newID);

		$art = $service->findById($newID);
		$this->assertInstanceOf('sly_Model_Article', $art);

		$this->assertEquals('my "article"', $art->getName());
		$this->assertEquals('', $art->getCatName());
		$this->assertEquals(1, $art->getPosition());
		$this->assertEquals('|', $art->getPath());
		$this->assertEquals(0, $art->getParentId());
		$this->assertTrue($art->isOnline());
	}

	public function testEdit() {
		$service = $this->getService();
		$id      = $service->add(0, 'my article', 1, -1);

		$service->edit($id, 1, 'new title', 0);

		$art = $service->findById($id);
		$this->assertEquals('new title', $art->getName());
		$this->assertEquals('', $art->getCatName());
	}

	public function testDelete() {
		$service = $this->getService();
		$id      = $service->add(0, 'tmp', 1, -1);

		$service->deleteById($id);

		$this->assertNull($service->findById($id));
	}

	public function testChangeStatus() {
		$service = $this->getService();
		$id      = $service->add(0, 'tmp', 1, -1);

		$this->assertTrue($service->findById($id, 1)->isOnline());
		$service->changeStatus($id, 1, 0);
		$this->assertFalse($service->findById($id, 1)->isOnline());
		$service->changeStatus($id, 1, 1);
		$this->assertTrue($service->findById($id, 1)->isOnline());
	}
}
