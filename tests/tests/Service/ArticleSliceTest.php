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
	public function testMove() {
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
