<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_ComposerTest extends PHPUnit_Framework_TestCase {
	private function getFile() {
		return realpath(dirname(__FILE__).'/../../../composer.json');
	}

	public function testSimpleStuff() {
		$file = $this->getFile();
		$util = new sly_Util_Composer($file);

		$this->assertEquals($file, $util->getFilename());
		$this->assertInternalType('array', $util->getContent());
		$this->assertEquals('sallycms/core', $util->getKey('name'));
		$this->assertEquals(array('cms', 'php', 'mysql', 'framework', 'sallycms'), $util->getKey('keywords'));
		$this->assertEquals(null, $util->getKey('mumblefoo'));
	}
}
