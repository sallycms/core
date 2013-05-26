<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_ArticleExTest extends sly_Service_ArticleTestBase {
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
	6: Kontakt
	7: Über Sally
	8: Impressum
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
			array('[[6,2]]',             array(7,6,8)),
			array('[[6,2],[6,3]]',       array(7,8,6)),
			array('[[6,2],[6,3],[6,1]]', array(6,7,8)),

			array('[[8,2]]',       array(6,8,7)),
			array('[[8,2],[8,3]]', array(6,7,8)),

			array('[[6,1]]', array(6,7,8)),

			// pseudo
			array('[[6,0]]',  array(7,8,6)),
			array('[[6,-1]]', array(7,8,6)),

			// out-of-range
			array('[[6,-7]]', array(7,8,6)),
			array('[[6,99]]', array(7,8,6)),
		);
	}

	public function testStartArticleMovements() {
		// create some more articles
		$service = $this->getService();
		$lang    = self::$clangA;

		$a = 1;
		$b = $service->add(1, 'A', -1);
		$c = $service->add(1, 'B', -1);

		// make sure everything is fine up to here
		$this->assertPositions(array($a, $b, $c), $lang);

		// and now move the start article around
		$this->move($a, 1, $lang); $this->assertPositions(array($a, $b, $c), $lang);
		$this->move($a, 2, $lang); $this->assertPositions(array($b, $a, $c), $lang);
		$this->move($a, 1, $lang); $this->assertPositions(array($a, $b, $c), $lang);
		$this->move($a, 3, $lang); $this->assertPositions(array($b, $c, $a), $lang);
		$this->move($a, 2, $lang); $this->assertPositions(array($b, $a, $c), $lang);
		$this->move($a, 1, $lang); $this->assertPositions(array($a, $b, $c), $lang);

		// move the other articles around and see if the startarticle's pos is OK
		$this->move($b, 1, $lang); $this->assertPositions(array($b, $a, $c), $lang);
		$this->move($c, 1, $lang); $this->assertPositions(array($c, $b, $a), $lang);
		$this->move($a, 2, $lang); $this->assertPositions(array($c, $a, $b), $lang);
		$this->move($c, 2, $lang); $this->assertPositions(array($a, $c, $b), $lang);

		//check if revision increased 7 times
		$t = $service->findByPK($a, $lang);
		$this->assertEquals($t->getRevision(), 7);
	}

	/**
	 * @dataProvider      illegalMoveProvider
	 * @expectedException sly_Exception
	 */
	public function testIllegalTreeMoves($id, $target) {
		$this->getService()->move($id, $target);
	}

	public function illegalMoveProvider() {
		return array(array(1,1), array(6,0), array(1,7));
	}

	/**
	 * @dataProvider findArticlesByCategoryProvider
	 */
	public function testFindArticlesByCategory($parent, $clang, array $expected) {
		$service = $this->getService();
		$arts    = $service->findArticlesByCategory($parent, $clang);

		foreach ($arts as &$art) {
			$art = $art->getId();
		}

		$this->assertEquals($expected, $arts);
	}

	public function findArticlesByCategoryProvider() {
		return array(
			array(0, self::$clangA, array(6,7,8)),
			array(0, self::$clangB, array(6,7,8)),
			array(1, self::$clangA, array(1)),
			array(1, self::$clangB, array(1)),
		);
	}

	public function testCopy() {
		$service  = $this->getService();
		$articles = array(6,7,8);
		$root     = 0;

		////////////////////////////////////////////////////////////
		// copy the article in it's own category (root)

		$newID = $service->copy(6, $root);
		$articles[] = $newID;

		$this->assertInternalType('int', $newID);

		$arts = $service->findArticlesByCategory($root, self::$clangA);
		$this->assertCount(4, $arts);

		foreach ($arts as $idx => $art) {
			$this->assertEquals($articles[$idx], $art->getId());
		}

		$last = array_pop($arts);

		$this->assertEquals($newID, $last->getId());
		$this->assertEquals(4, $last->getPosition());
		$this->assertEquals('', $last->getCatName());

		// the same should apply to the B language

		$arts = $service->findArticlesByCategory($root, self::$clangB);
		$this->assertCount(4, $arts);

		$service->deleteById($newID);

		////////////////////////////////////////////////////////////
		// copy the article in another category

		$cat   = 1;
		$newID = $service->copy(6, $cat);

		$this->assertInternalType('int', $newID);

		$arts = $service->findArticlesByCategory($cat, self::$clangA);
		$this->assertCount(2, $arts);
		$this->assertEquals($newID, end($arts)->getId());
		$this->assertEquals(reset($arts)->getName(), end($arts)->getCatName());
	}

	/**
	 * @depends testCopy
	 *
	 * Check if the article service can copy a start article
	 *
	 * This makes sure that the article service correctly sets startpage = 0
	 * and catpos = 0 (and makes the copy a true article instead of a shallow
	 * category copy).
	 */
	public function testCopyStartArticle() {
		$service = $this->getService();
		$newID   = $service->copy(1, 0);

		$this->assertInternalType('int', $newID);

		$art = $service->findByPK($newID, self::$clangA);
		$this->assertEquals(0, $art->getStartpage());
		$this->assertEquals(0, $art->getCatPosition());

		// check of copy copies content
		$sliceS  = sly_Core::getContainer()->getArticleSliceService();

		$oldSlices = $sliceS->find(array('article_id' => 1,      'clang' => self::$clangA), null, 'slot ASC, pos ASC');
		$newSlices = $sliceS->find(array('article_id' => $newID, 'clang' => self::$clangA), null, 'slot ASC, pos ASC');

		$this->assertCount(4, $oldSlices);
		$this->assertCount(4, $newSlices);

		foreach ($oldSlices as $idx => $oldSlice) {
			$newSlice = $newSlices[$idx];

			$this->compareSlices($oldSlice, $newSlice);

			$this->assertEquals($oldSlice->getClang(), $newSlice->getClang());
			$this->assertNotEquals($oldSlice->getArticleId(), $newSlice->getArticleId());
			$this->assertNotEquals($oldSlice->getSliceId(), $newSlice->getSliceId());
		}
	}

	private function compareSlices(sly_Model_ArticleSlice $oldSlice, sly_Model_ArticleSlice $newSlice) {
		$this->assertEquals($oldSlice->getSlot(), $newSlice->getSlot());
		$this->assertEquals($oldSlice->getPosition(), $newSlice->getPosition());
		$this->assertGreaterThan($oldSlice->getCreatedate(), $newSlice->getCreatedate());
//		$this->assertEquals($oldSlice->getUpdatedate(), $newSlice->getUpdatedate()); // get reset on copy to time()
		$this->assertEquals($oldSlice->getCreateuser(), $newSlice->getCreateuser());
		$this->assertEquals($oldSlice->getUpdateuser(), $newSlice->getUpdateuser());
	}

	public function testMove() {
		$service  = $this->getService();

		////////////////////////////////////////////////////////////
		// move one article to the first cat

		$service->move(7, 1);
		$this->assertPositions(array(6,8), self::$clangA);
		$this->assertPositions(array(1,7), self::$clangA);

		$art = $service->findByPK(7, self::$clangA);
		$this->assertEquals($service->findByPK(1, self::$clangA)->getCatName(), $art->getCatName());
		$this->assertEquals(2, $art->getPosition());

		$this->assertCount(2, $service->findArticlesByCategory(0, self::$clangA, false));
		$this->assertCount(2, $service->findArticlesByCategory(1, self::$clangA, false));
		$this->assertCount(2, $service->findArticlesByCategory(1, self::$clangA, true));

		////////////////////////////////////////////////////////////
		// move it back

		$service->move(7, 0);
		$this->assertPositions(array(6,8,7), self::$clangA);
		$this->assertPositions(array(1), self::$clangA);

		$this->assertCount(3, $service->findArticlesByCategory(0, self::$clangA));
		$this->assertCount(1, $service->findArticlesByCategory(1, self::$clangA));
	}
}
