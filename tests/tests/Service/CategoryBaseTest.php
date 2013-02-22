<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_CategoryBaseTest extends sly_Service_CategoryTestBase {
	protected static $clang = 5;
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
		$newID   = $service->add(0, 'my "category"', 1, -1);

		$this->assertInternalType('int', $newID);

		$cat = $service->findByPK($newID, self::$clang);
		$this->assertInstanceOf('sly_Model_Category', $cat);

		$this->assertEquals('my "category"', $cat->getName());
		$this->assertEquals('my "category"', $cat->getCatName());
		$this->assertEquals(1, $cat->getPosition());
		$this->assertEquals('|', $cat->getPath());
		$this->assertEquals(0, $cat->getParentId());
		$this->assertTrue($cat->isOnline());
	}

	public function testEdit() {
		$service = $this->getService();
		$id      = $service->add(0, 'my category', 1, -1);
		$cat     = $service->findByPK($id, self::$clang);

		$service->edit($cat, 'new title', 0);

		$cat = $service->findByPK($id, self::$clang);
		$this->assertEquals('new title', $cat->getName());
		$this->assertEquals('new title', $cat->getCatName());
	}

	public function testDelete() {
		$service = $this->getService();
		$id      = $service->add(0, 'tmp', 1, -1);

		$service->deleteById($id);

		$this->assertNull($service->findByPK($id, self::$clang));
	}

	/**
	 * @depends testDelete
	 * @expectedException  sly_Exception
	 */
	public function testDeleteNonExisting() {
		$service = $this->getService();
		$service->deleteById(2);
	}
}
