<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_CategoryExTest extends sly_Service_CategoryBase {
	protected function getDataSetName() {
		return 'sally-demopage';
	}

	private function reset() {
		for ($i = 1; $i <= 5; ++$i) $this->move($i, $i);
	}

	private function moves($moves, $clang = 1) {
		$moves = json_decode($moves, true);

		foreach ($moves as $move) {
			$this->move($move[0], $move[1], $clang);
		}
	}

	private function makeMove($id, $to, array $expected, $clang = 1) {
		$this->move($id, $to, $clang);
		$this->assertPositions($expected, $clang);
	}

	private function move($id, $to, $clang = 1) {
		$cat = $this->getService()->findById($id, $clang);
		$this->getService()->edit($id, $clang, $cat->getCatname(), $to);
	}

	protected function assertTree($tree, $clang = 1, $parent = 1) {
		$tree = is_string($tree) ? json_decode($tree, true) : $tree;

		// empty sublist?
		if (count($tree) === 0) return;

		// sublist given as integer?
		if (!is_array($tree)) $tree = array($tree);

		$pos = 1;

		// $tree = array(1 => array(2 => array(3)))
		foreach ($tree as $catID => $children) {
			if (!is_array($children)) {
				$cat      = $children;
				$children = array();
			}

			$cat = $service->findById($catID, $clang);
			$msg = 'Parent of category '.$catID.' should be '.$parent.'.';

			$this->assertEquals($parent, $cat->getParentId(), $msg);
			$this->assertPositions($id, $pos, $clang);

			$this->assertTree($children, $clang, $catID);
			++$pos;
		}
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
		$this->moves($moves);
		$this->assertPositions($expected);
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
		require_once SLY_COREFOLDER.'/functions/function_rex_content.inc.php';

		$moves = json_decode($moves, true);

		foreach ($moves as $move) {
			rex_moveCategory($move[0], $move[1]);
		}

		$this->assertTree($expected);
	}

	public function treeMovesProvider() {
		return array(
			array('[[3,1],[4,1]]',                         '{0:[1,2,5],1:[3,4]}'),
			array('[[3,1],[4,1],[4,3]]',                   '{0:[1,2,5],1:{3:4}}'),
			array('[[3,1],[4,1],[4,3],[3,2]]',             '{0:[1,2,5],2:{3:4}}'),
			array('[[3,1],[4,1],[4,3],[3,2],[4,0],[3,0]]', '{0:[1,2,5,4,3]}')
		);
	}

	/**
	 * @depends testTreeMoves
	 */
	public function testIllegalTreeMoves() {
		rex_moveCategory(2, 1);
		rex_moveCategory(3, 2);

		$this->assertFalse(rex_moveCategory(1, 1), 'Do not allow to move category into itself (simple case).');
		$this->assertFalse(rex_moveCategory(1, 3), 'Do not allow to move category into itself (recursion).');
		$this->assertFalse(rex_moveCategory(1, 7), 'Do not allow to move into non-existing category.');
	}

	/**
	 * @depends testTreeMoves
	 */
	public function testArticle2Startpage() {
		// create some articles
		$service = sly_Service_Factory::getArticleService();

		$a = $service->add(1, 'test article 1', 1);
		$b = $service->add(1, 'test article 2', 1); // our new startarticle
		$c = $service->add(1, 'test article 3', 1);

		// make sure some categories have to be relinked
		rex_moveCategory(2, 1);
		rex_moveCategory(3, 1);
		rex_moveCategory(5, 2);

		// current tree: {1: {2: [5], 3}, 4}
		rex_article2startpage($b);

		$this->assertTree('{0:{'.$b.':{2: [5], 3}, 4}}');

		$this->assertEquals(1, $service->findById(1)->getPrior());
		$this->assertEquals(2, $service->findById($a)->getPrior());
		$this->assertEquals(3, $service->findById($b)->getPrior());
		$this->assertEquals(4, $service->findById($c)->getPrior());
	}

	/**
	 * @dataProvider findByParentIdProvider
	 */
	public function testFindByParentId($parent, $ignoreOffline, $clang, array $expected) {
		$service = $this->getService();
		$cats    = $service->findByParentId($parent, $ignoreOffline, $clang);

		foreach ($cats as &$cat) {
			$cat = $cat->getId();
		}

		$this->assertEquals($expected, $cats);
	}

	public function findByParentIdProvider() {
		return array(
			array(0, false, 1, array(1,2,3,4,5)), array(0, true, 1, array(1,2,3,5)),
			array(0, false, 2, array(1,2,3,4,5)), array(0, true, 2, array()),
			array(1, false, 1, array()), array(1, true, 1, array())
		);
	}
}
