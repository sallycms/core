<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_LoaderTest extends PHPUnit_Framework_TestCase {
	public function testLoadExisting() {
		$classToTest = 'sly_Session';
		sly_Loader::loadClass($classToTest);
		return $this->assertTrue(class_exists($classToTest, false));
	}

	public function testLoadNotExisting() {
		$classToTest = 'sly_Session'.uniqid();
		sly_Loader::loadClass($classToTest);
		return $this->assertFalse(class_exists($classToTest, false));
	}
}
