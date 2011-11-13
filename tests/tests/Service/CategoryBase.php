<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_CategoryBase extends sly_StructureTest {
	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getCategoryService();
		return $service;
	}

	protected function assertPositions(array $expected, $clang = 1) {
		foreach ($expected as $idx => $catID) {
			if ($catID === null) continue;
			$this->assertPosition($catID, $idx + 1, $clang);
		}
	}

	protected function assertPosition($id, $pos, $clang = 1) {
		$service = $this->getService();
		$cat     = $service->findById($id, $clang);
		$msg     = 'Position of category '.$id.' should be '.$pos.'.';

		$this->assertEquals($pos, $cat->getCatprior(), $msg);
	}
}
