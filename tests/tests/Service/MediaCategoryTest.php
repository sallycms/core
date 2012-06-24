<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_MediaCategoryTest extends sly_BaseTest {
	protected function getDataSetName() {
		return 'pristine-sally';
	}

	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getMediaCategoryService();
		return $service;
	}

	public function testAdd() {
		$service = $this->getService();
		$cat     = $service->add('Meine Kategorie', null);

		$this->assertInstanceOf('sly_Model_MediaCategory', $cat);
		$this->assertEquals('Meine Kategorie', $cat->getName());

		$cat = $service->findById($cat->getId());
		$this->assertInstanceOf('sly_Model_MediaCategory', $cat);

		$cats = $service->findByParentId(0);
		$this->assertCount(1, $cats);
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service = $this->getService();
		$cat     = $service->add('Meine Kategorie', null);

		$cat->setName('Foo');
		$service->update($cat);

		$cat = $service->findById($cat->getId());
		$this->assertEquals('Foo', $cat->getName());
	}

	/**
	 * @depends testAdd
	 */
	public function testDelete() {
		$service = $this->getService();
		$catID   = $service->add('Meine Kategorie', null)->getId();

		$service->deleteById($catID);
		$this->assertNull($service->findById($catID));

		$cats = $service->findByParentId(0);
		$this->assertEmpty($cats);
	}

	/**
	 * @depends testAdd
	 */
	public function testTree() {
		$service = $this->getService();

		/*
		A
		+- D
		|  +- F
		|  E
		B
		C
		*/

		$catA = $service->add('0', null);
		$catB = $service->add('0', null);
		$catC = $service->add('0', null);

		$catD = $service->add('A', $catA);
		$catE = $service->add('A', $catA);

		$catF = $service->add('D', $catD);

		// can we find them?

		$this->assertEquals(array($catA, $catB, $catC), $service->findByName('0'));
		$this->assertEquals(array($catD, $catE),        $service->findByName('A'));

		// from now on we only need the IDs

		foreach (array('A', 'B', 'C', 'D', 'E', 'F') as $char) {
			$var = 'cat'.$char; $$var = $$var->getId();
		}

		// assert some counts

		$cats = $service->findByParentId(0);     $this->assertCount(3, $cats);
		$cats = $service->findByParentId($catA); $this->assertCount(2, $cats);
		$cats = $service->findByParentId($catD); $this->assertCount(1, $cats);
		$cats = $service->findByParentId($catB); $this->assertEmpty($cats);
		$cats = $service->findByParentId(-42);   $this->assertEmpty($cats);

		// assert trees

		$this->assertEquals(array($catB), $service->findTree($catB, false));
		$this->assertEquals(array($catF), $service->findTree($catF, false));
		$this->assertEquals(array($catD, $catF), $service->findTree($catD, false));
		$this->assertEquals(array($catA, $catD, $catE, $catF), $service->findTree($catA, false));
		$this->assertEquals(array($catA, $catB, $catC, $catD, $catE, $catF), $service->findTree(0, false));

		// easy deletions

		$service->deleteById($catB);
		$service->deleteById($catC);

		// complex cases

		try {
			$service->deleteById($catD);
			$this->fail('Deleting categories with children should not be possible without $force.');
		}
		catch (sly_Exception $e) {
			#win
		}

		// and now use the $force
		$service->deleteById($catD, true);
		$service->deleteById($catA, true);
	}
}
