<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_CategoryExTest extends sly_Service_CategoryTestBase {
	private static $clangA = 5;
	private static $clangB = 7;

	protected function getDataSetName() {
		return 'sally-demopage';
	}

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		sly_Core::setCurrentClang(self::$clangA);
	}

	/*
	1: Was Sally für Sie tun kann
	2: Wie Sally für Sie arbeitet
	3: Was Sally einfach besser macht
	4: Warum unsere Sally?
	5: Antwort auf Ihre Fragen
	*/

	/**
	 * @dataProvider movementsProvider
	 */
	public function testMovements($moves, array $expected) {
		$this->moves($moves, self::$clangA);
		$this->assertPositions($expected, self::$clangA);
	}

	public function movementsProvider() {
		return array(
			// valid
			array('[[1,3]]',             array(2,3,1,4,5)),
			array('[[1,3],[1,5]]',       array(2,3,4,5,1)),
			array('[[1,3],[1,5],[1,1]]', array(1,2,3,4,5)),

			array('[[4,2]]',       array(1,4,2,3,5)),
			array('[[4,2],[2,2]]', array(1,2,4,3,5)),

			array('[[3,3]]', array(1,2,3,4,5)),

			// pseudo
			array('[[1,0]]',  array(2,3,4,5,1)),
			array('[[1,-1]]', array(2,3,4,5,1)),

			// out-of-range
			array('[[1,-7]]', array(2,3,4,5,1)),
			array('[[1,99]]', array(2,3,4,5,1)),
		);
	}

	/**
	 * @dataProvider treeMovesProvider
	 */
	public function testTreeMoves($moves, $expected) {
		$moves   = json_decode($moves, true);
		$service = sly_Core::getContainer()->getCategoryService();

		foreach ($moves as $move) {
			$service->move($move[0], $move[1]);
		}

		//$this->assertTree($expected, self::$clangA);
	}

	public function treeMovesProvider() {
		return array(
			array('[[3,1],[4,1]]',                         '1<3,4>,2,5'),
			array('[[3,1],[4,1],[4,3]]',                   '1<3<4>>,2,5'),
			array('[[3,1],[4,1],[4,3],[3,2]]',             '1,2<3<4>>,5'),
			array('[[3,1],[4,1],[4,3],[3,2],[4,0],[3,0]]', '1,2,5,4,3')
		);
	}

	/**
	 * @depends           testTreeMoves
	 * @dataProvider      illegalTreeMovesProvider
	 * @expectedException sly_Exception
	 */
	public function testIllegalTreeMoves($from, $to) {
		$service = sly_Core::getContainer()->getCategoryService();
		$service->move(2, 1);
		$service->move(3, 2);

		$service->move($from, $to);
	}

	public function illegalTreeMovesProvider() {
		return array(
			array(1, 1),
			array(1, 3),
			array(1, 7)
		);
	}

	/**
	 * @depends testTreeMoves
	 */
	public function testConvertToStartArticle() {
		// create some articles
		$service    = sly_Core::getContainer()->getArticleService();
		$catService = sly_Core::getContainer()->getCategoryService();

		$a = $service->add(1, 'test article 1', 2);
		$b = $service->add(1, 'test article 2', 3); // our new startarticle
		$c = $service->add(1, 'test article 3', 4);

		// make sure some categories have to be relinked
		$catService->move(2, 1);
		$catService->move(3, 1);
		$catService->move(5, 2);

		// current tree: 1<2<5>,3>,4
		$service->convertToStartArticle($b);

		$this->assertTree($b.'<2<5>,3>,4', self::$clangA);

		$this->assertEquals(1, $service->findByPK(1,  self::$clangA)->getPosition());
		$this->assertEquals(2, $service->findByPK($a, self::$clangA)->getPosition());
		$this->assertEquals(3, $service->findByPK($b, self::$clangA)->getPosition());
		$this->assertEquals(4, $service->findByPK($c, self::$clangA)->getPosition());
	}

	/**
	 * @dataProvider findByParentIdProvider
	 */
	public function testFindByParentId($parent, $ignoreOffline, $clang, array $expected) {
		$service = $this->getService();
		$cats    = $service->findByParentId($parent, $clang, $ignoreOffline);

		foreach ($cats as &$cat) {
			$cat = $cat->getId();
		}

		$this->assertEquals($expected, $cats);
	}

	public function findByParentIdProvider() {
		return array(
			array(0, false, self::$clangA, array(1,2,3,4,5)),
			array(0, false, self::$clangB, array(1,2,3,4,5)),
			array(1, false, self::$clangA, array()),
		);
	}

	public function testDeleteCancelledIfChildrenExist() {
		$service = $this->getService();
		$parent  = 2;

		// create some children
		$service->add($parent, 'A', -1);
		$B = $service->add($parent, 'B', -1);
		$service->add($parent, 'C', -1);

		// and some children inside B
		$service->add($B, 'X', -1);
		$service->add($B, 'Y', -1);
		$service->add($B, 'Z', -1);

		try {
			// boom
			$service->deleteById($B);
			$this->fail('Should not have been able to delete a category with children.');
		}
		catch (sly_Exception $e) {
			$this->assertTrue(true);
		}

		try {
			// boom
			$service->deleteById($parent);
			$this->fail('Should not have been able to delete a category with children.');
		}
		catch (sly_Exception $e) {
			$this->assertTrue(true);
		}
	}

	/**
	 * @expectedException  sly_Exception
	 */
	public function testDeleteCancelledIfChildArticlesExist() {
		$cservice = $this->getService();
		$aservice = sly_Core::getContainer()->getArticleService();
		$parent   = 2;

		// create some children
		$aservice->add($parent, 'A', -1);
		$aservice->add($parent, 'B', -1);

		// boom
		$cservice->deleteById($parent);
	}
}
