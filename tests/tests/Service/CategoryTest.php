<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_CategoryTest extends PHPUnit_Framework_TestCase {
	private static $id;

	public static function setUpBeforeClass() {
		$service = sly_Service_Factory::getCategoryService();
		self::$id = $service->add(0, 'Testkategorie', true, -1);
	}

	public static function tearDownAfterClass() {
		$service = sly_Service_Factory::getCategoryService();
		$service->delete(self::$id);
	}

	public function testAdd() {
		$service = sly_Service_Factory::getCategoryService();
		$newID   = $service->add(0, 'Meine "Kategorie"', true, -1);

		$this->assertInternalType('int', $newID);

		$cat = $service->findById($newID);
		$this->assertEquals('Meine "Kategorie"', $cat->getName());
		$this->assertEquals('Meine "Kategorie"', $cat->getCatname());
		$this->assertEquals(1, $cat->getPrior());
		$this->assertEquals('|', $cat->getPath());
		$this->assertEquals(0, $cat->getParentId());

		$service->delete($newID);
		$this->assertNull($service->findById($newID, 0));
	}

	public function testEdit() {
		$service = sly_Service_Factory::getCategoryService();
		$service->edit(self::$id, 1, 'Neuer Titel', false);

		$cat = $service->findById(self::$id);
		$this->assertEquals('Neuer Titel', $cat->getName());
		$this->assertEquals('Neuer Titel', $cat->getCatname());
	}
}
