<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
		$b = $service->add(1, 'A', 1, -1);
		$c = $service->add(1, 'B', 1, -1);

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
	}

	/**
	 * @dataProvider      illegalMoveProvider
	 * @expectedException sly_Exception
	 */
	public function testIllegalTreeMoves($id, $target) {
		$this->getService()->move($id, $target, self::$clangA);
	}

	public function illegalMoveProvider() {
		return array(array(1,1), array(6,0), array(1,7));
	}

	/**
	 * @dataProvider findArticlesByCategoryProvider
	 */
	public function testFindArticlesByCategory($parent, $ignoreOffline, $clang, array $expected) {
		$service = $this->getService();
		$arts    = $service->findArticlesByCategory($parent, $ignoreOffline, $clang);

		foreach ($arts as &$art) {
			$art = $art->getId();
		}

		$this->assertEquals($expected, $arts);
	}

	public function findArticlesByCategoryProvider() {
		return array(
			array(0, false, self::$clangA, array(6,7,8)), array(0, true, self::$clangA, array(6,7,8)),
			array(0, false, self::$clangB, array(6,7,8)), array(0, true, self::$clangB, array()),
			array(1, false, self::$clangA, array(1)),
			array(1, true,  self::$clangA, array(1)),
			array(1, true,  self::$clangB, array())
		);
	}

	public function testTouch() {
		$service = $this->getService();
		$article = $service->findById(1, self::$clangA);
		$user    = sly_Service_Factory::getUserService()->findById(1);

		$before = time();
		$service->touch($article, $user);
		$after = time();

		$article = $service->findById(1, self::$clangA);

		$this->assertGreaterThanOrEqual($before, $article->getUpdateDate());
		$this->assertLessThanOrEqual($after, $article->getUpdateDate());
		$this->assertEquals($user->getLogin(), $article->getUpdateUser());
	}

	public function testSetType() {
		$service = $this->getService();
		$article = $service->findById(6, self::$clangA);

		$service->setType($article, 'special');

		// type must be the same in all languages
		foreach (array(self::$clangA, self::$clangB) as $clang) {
			$article = $service->findById(6, $clang);
			$this->assertEquals('special', $article->getType());
		}
	}

	public function testFindByType() {
		$artA    = 6;
		$artB    = 7;
		$service = $this->getService();

		$this->assertEmpty($service->findArticlesByType('special'));

		// make A & B special articles

		$service->setType($service->findById($artA), 'special');
		$service->setType($service->findById($artB), 'special');
		$result = $service->findArticlesByType('special');

		$this->assertCount(2, $result);

		foreach (array($artA, $artB) as $idx => $artId) {
			$article = $service->findById($artId);
			$this->assertEquals($article, $result[$idx]);
		}

		// set A offline

		$service->changeStatus($artA, self::$clangA, 0);
		$result = $service->findArticlesByType('special');

		$this->assertCount(2, $result);

		foreach (array($artA, $artB) as $idx => $artId) {
			$article = $service->findById($artId);
			$this->assertEquals($article, $result[$idx]);
		}

		// when ignoring offline articles, don't expect A

		$result = $service->findArticlesByType('special', true);

		$this->assertCount(1, $result);
		$this->assertEquals($service->findById($artB), $result[0]);
	}

	public function testCopy() {
		$service  = $this->getService();
		$articles = array(6,7,8);
		$root     = 0;

		////////////////////////////////////////////////////////////
		// copy the article in it's own category (root)

		$newID = $service->copy(6, $root);

		$this->assertInternalType('int', $newID);

		// since the new article is offline, expect the original article list

		$arts = $service->findArticlesByCategory($root, true);
		$this->assertCount(3, $arts);

		foreach ($arts as $idx => $art) {
			$this->assertEquals($articles[$idx], $art->getId());
		}

		// and now let's include the offline article

		$arts = $service->findArticlesByCategory($root, false);
		$this->assertCount(4, $arts);
		$last = array_pop($arts);

		foreach ($arts as $idx => $art) {
			$this->assertEquals($articles[$idx], $art->getId());
		}

		$this->assertEquals($newID, $last->getId());
		$this->assertEquals(4, $last->getPosition());
		$this->assertEquals('', $last->getCatName());

		// the same should apply to the B language

		$arts = $service->findArticlesByCategory($root, false, self::$clangB);
		$this->assertCount(4, $arts);

		$service->delete($newID);

		////////////////////////////////////////////////////////////
		// copy the article in another category

		$cat   = 1;
		$newID = $service->copy(6, $cat);

		$this->assertInternalType('int', $newID);

		$arts = $service->findArticlesByCategory($cat, true);
		$this->assertCount(1, $arts);

		$arts = $service->findArticlesByCategory($cat, false);
		$this->assertCount(2, $arts);
		$this->assertEquals($newID, end($arts)->getId());
		$this->assertEquals(reset($arts)->getName(), end($arts)->getCatName());
	}

	public function testMove() {
		$service  = $this->getService();
		$articles = array(6,7,8);

		////////////////////////////////////////////////////////////
		// move one article to the first cat

		$service->move(7, 1);
		$this->assertPositions(array(6,8), self::$clangA);
		$this->assertPositions(array(1,7), self::$clangA);

		$art = $service->findById(7);
		$this->assertEquals($service->findById(1)->getCatName(), $art->getCatName());
		$this->assertEquals(2, $art->getPosition());

		$this->assertCount(2, $service->findArticlesByCategory(0, false));
		$this->assertCount(2, $service->findArticlesByCategory(1, false));
		$this->assertCount(1, $service->findArticlesByCategory(1, true));

		////////////////////////////////////////////////////////////
		// move it back

		$service->move(7, 0);
		$this->assertPositions(array(6,8,7), self::$clangA);
		$this->assertPositions(array(1), self::$clangA);

		$this->assertCount(3, $service->findArticlesByCategory(0, false));
		$this->assertCount(2, $service->findArticlesByCategory(0, true));
		$this->assertCount(1, $service->findArticlesByCategory(1, false));
	}
}
