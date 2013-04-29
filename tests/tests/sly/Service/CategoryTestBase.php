<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_CategoryTestBase extends sly_StructureTest {
	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Core::getContainer()->getCategoryService();
		return $service;
	}

	protected function assertPosition($id, $pos, $clang = 5) {
		$service = $this->getService();
		$cat     = $service->findByPK($id, $clang);
		$msg     = 'Position of category '.$id.' should be '.$pos.'.';

		$this->assertEquals($pos, $cat->getCatPosition(), $msg);
	}

	protected function move($id, $to, $clang) {
		$cat = $this->getService()->findByPK($id, $clang);
		$this->getService()->edit($cat, $cat->getCatName(), $to);
	}
}
