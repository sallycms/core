<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleTestBase extends sly_StructureTest {
	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Core::getContainer()->getArticleService();
		return $service;
	}

	protected function assertPosition($id, $pos, $clang = 1) {
		$service = $this->getService();
		$art     = $service->findByPK($id, $clang);
		$msg     = 'Position of article '.$id.' should be '.$pos.'.';

		$this->assertEquals($pos, $art->getPosition(), $msg);
	}

	protected function move($id, $to, $clang) {
		$art = $this->getService()->findByPK($id, $clang);

		if (!$art) {
			$this->fail('Could not find article '.$id.'/'.$clang.'.');
		}

		$this->getService()->edit($art, $art->getName(), $to);
	}
}
