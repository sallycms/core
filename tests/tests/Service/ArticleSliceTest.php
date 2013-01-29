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
		return 'sally-demopage';
	}

	protected function getService() {
		return sly_Core::getContainer()->getArticleSliceService();
	}

	protected function getArticleService() {
		return sly_Core::getContainer()->getArticleService();
	}

	protected function getUser() {
		return sly_Service_Factory::getUserService()->findById(1);
	}

	public function testAdd() {
		$service = $this->getService();
		$article = $this->getArticleService()->findById(1, 5);

		$slice = $service->add($article, 'test', 'test1', array('test' => 'not empty'), 0, $this->getUser());
		$this->assertEquals('test1', $slice->getModule());

		$articleNewRevision = $this->getArticleService()->findById(1, 5);
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service = $this->getService();
		$article = $this->getArticleService()->findById(1, 5);

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), 0, $this->getUser());
		$article = $this->getArticleService()->findById(1, 5);
		$slice   = $service->edit($article, 'test', 0, array('test' => 'not empty test'), $this->getUser());
		$this->assertEquals('not empty test', $slice->getValue('test'));

		$articleNewRevision = $this->getArticleService()->findById(1, 5);
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
	}

	public function testMove() {
		$service = $this->getService();
		$article = $this->getArticleService()->findById(1, 5);

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), -1, $this->getUser());
		$article = $this->getArticleService()->findById(1, 5);
		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty test'), 2, $this->getUser());
		$article = $this->getArticleService()->findById(1, 5);
		$service->moveTo($article, 'test', 1, 0, $this->getUser());

		$articleNewRevision = $this->getArticleService()->findById(1, 5);
		$slices             = $articleNewRevision->getSlices('test');

		$this->assertEquals('not empty test', $slices[0]->getValue('test'));
		$this->assertEquals('not empty', $slices[1]->getValue('test'));
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
	}

	public function testDelete() {
		$service = $this->getService();
		$article = $this->getArticleService()->findById(1, 5);

		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty'), -1, $this->getUser());
		$service->deleteByArticleSlice($slice);
		$articleNewRevision = $this->getArticleService()->findById(1, 5);

		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());
		$this->assertEmpty($articleNewRevision->getSlices('test'));


		$slice   = $service->add($article, 'test', 'test1', array('test' => 'not empty test'), 2, $this->getUser());
		$article = $this->getArticleService()->findById(1, 5);
		$service->delete($article, 'test');
		$article = $this->getArticleService()->findById(1, 5);

		$this->assertEmpty($article->getSlices('test'));
		$this->assertNotEmpty($article->getSlices());

		$service->delete($article);
		$articleNewRevision = $this->getArticleService()->findById(1, 5);

		$this->assertEmpty($articleNewRevision->getSlices());
		$this->assertGreaterThan($article->getRevision(), $articleNewRevision->getRevision());

	}

}
