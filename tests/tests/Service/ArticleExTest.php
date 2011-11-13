<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_ArticleExTest extends sly_Service_ArticleBase {
	protected function getDataSetName() {
		return 'sally-demopage';
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
		$this->moves($moves);
		$this->assertPositions($expected);
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

	public function testIllegalTreeMoves() {
		$this->assertFalse(rex_moveArticle(1, 1), 'Do not allow to move category with article API.');
		$this->assertFalse(rex_moveArticle(6, 0), 'Do not allow to move article into current position.');
		$this->assertFalse(rex_moveArticle(1, 7), 'Do not allow to move article into non-existing category.');
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
			array(0, false, 1, array(6,7,8)), array(0, true, 1, array(6,7,8)),
			array(0, false, 2, array(6,7,8)), array(0, true, 2, array()),
			array(1, false, 1, array(1)), array(1, true, 1, array(1)), array(1, true, 2, array())
		);
	}
}
