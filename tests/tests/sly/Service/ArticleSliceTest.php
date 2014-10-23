<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class ArticleSliceTest extends sly_BaseTest {

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	protected function dummyArticle() {
		$aservice = $this->getArticleService();
		$aid      = $aservice->add(0, 'Test');

		$article  = $aservice->findByPK($aid, 5);
		$aservice->setType($article, 'default');

		return $aservice->findByPK($aid, 5);
	}

	protected function getService() {
		return sly_Core::getContainer()->getArticleSliceService();
	}

	protected function getArticleService() {
		return sly_Core::getContainer()->getArticleService();
	}

	public function testAdd() {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();

		$slice = $service->add($article, 'test', 'test1', array('test' => '1'), 0);
		$this->assertNotSame(sly_Model_Base_Id::NEW_ID, $slice->getId());
		$this->assertEquals('test1', $slice->getModule());
		$this->assertEquals(array('test' => '1'), $slice->getValues());

		$article  = $aservice->findByPK($aid, 5);
		$service->add($article, 'test', 'test1', array('test' => '0'), -1);
		$article  = $aservice->findByPK($aid, 5);
		$service->add($article, 'test', 'test1', array('test' => '2'), 4);

		$articleNewRevision = $aservice->findByPK($aid, 5);
		$slices = $articleNewRevision->getSlices();
		$pos = 0;
		foreach ($slices as $slice) {
			$this->assertEquals($pos, $slice->getPosition());
			$pos++;
		}
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
		$this->assertNotEmpty($articleNewRevision->getSlices());
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), 0);
		$article = $aservice->findByPK($aid, 5);
		$slice   = $service->edit($article, 'test', 0, array('test' => 'not empty test'));
		$this->assertEquals('not empty test', $slice->getValue('test'));

		$articleNewRevision = $aservice->findByPK(1, 5);
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
	}

	/**
	 * @depends testAdd
	 */
	public function testMovingSlicesMovesContentAsWell() {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), -1);
		$article = $aservice->findByPK($aid, 5);
		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty test'), 2);
		$article = $aservice->findByPK($aid, 5);
		$service->moveTo($article, 'test', 1, 0);

		$articleNewRevision = $aservice->findByPK($aid, 5);
		$slices             = $articleNewRevision->getSlices('test');

		$this->assertEquals('not empty test', $slices[0]->getValue('test'));
		$this->assertEquals('not empty', $slices[1]->getValue('test'));
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
	}

	/**
	 * @depends       testMovingSlicesMovesContentAsWell
	 * @dataProvider  generalMovementPatternsProvider
	 */
	public function testGeneralMovementPatterns($oldPos, $newPos, $expectedOrder) {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();
		$slices   = array();
		$slot     = 'test';
		$module   = 'test1';
		$clang    = 5;

		// create four dummy slices

		for ($i = 0; $i < 4; ++$i) {
			// put values like 'a', 'b', 'c', ... in the slice, so we can later check their
			// order (as their IDs will change when the article is touched)
			$slice   = $service->add($article, $slot, $module, array('char' => chr(97 + $i)));
			$article = $aservice->findByPK($aid, $clang); // re-fetch due to changed revision
		}

		// perform the move operation

		$service->moveTo($article, $slot, $oldPos, $newPos);

		// query the new slice order

		$article   = $aservice->findByPK($aid, $clang);
		$newSlices = $service->findByArticle($article, $slot);
		$newOrder  = '';
		$pos       = 0;

		foreach ($newSlices as $newSlice) {
			$newOrder .= $newSlice->getValue('char');

			// make sure the object has the correct pos value
			// (this one can be broken even though the order of all slices is okay, like in 0 3 4 42)
			$this->assertSame($pos++, $newSlice->getPosition());
		}

		$this->assertSame($expectedOrder, $newOrder);
	}

	public function generalMovementPatternsProvider() {
		// initial order is 'abcd', where 'a' is pos 0, 'b' is pos 1 etc.

		return array(
			array(0, 1,   'bacd'),
			array(1, 0,   'bacd'),
			array(0, 2,   'bcad'),
			array(0, 3,   'bcda'),
			array(0, 4,   'bcda'), // should be normalized automatically
			array(0, 420, 'bcda'), // dito
			array(3, 0,   'dabc'),
			array(3, 2,   'abdc')
		);
	}

	/**
	 * @depends           testGeneralMovementPatterns
	 * @dataProvider      illegalMovementPatternsProvider
	 * @expectedException sly_Exception
	 */
	public function testIllegalMovementPatterns($oldPos, $newPos) {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();
		$slices   = array();
		$slot     = 'test';
		$module   = 'test1';
		$clang    = 5;

		// create four dummy slices

		for ($i = 0; $i < 4; ++$i) {
			$slice   = $service->add($article, $slot, $module, array());
			$article = $aservice->findByPK($aid, $clang); // re-fetch due to changed revision
		}

		// perform the move operation (this should explode)

		$service->moveTo($article, $slot, $oldPos, $newPos);
	}

	public function illegalMovementPatternsProvider() {
		return array(
			array(-1,  1),
			array(420, 0),
			array(0,   0),
			array(420, 420)
		);
	}

	/**
	 * @depends testAdd
	 */
	public function testDelete() {
		$service  = $this->getService();
		$aservice = $this->getArticleService();
		$article  = $this->dummyArticle();
		$aid      = $article->getId();

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), 0);
		$service->deleteByArticleSlice($slice);
		$articleNewRevision = $aservice->findByPK($aid, 5);

		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
		$this->assertEmpty($articleNewRevision->getSlices('test'));


		$slice   = $service->add($article, 'main', 'test1', array('test' => 'not empty test'), 0);
		$article = $aservice->findByPK($aid, 5);
		$service->delete($article, 'test');
		$article = $aservice->findByPK($aid, 5);

		$this->assertEmpty($article->getSlices('test'));
		$this->assertNotEmpty($article->getSlices());

		$service->delete($article);
		$articleNewRevision = $aservice->findByPK($aid, 5);

		$this->assertEmpty($articleNewRevision->getSlices());
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());

	}

}
