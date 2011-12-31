<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_DirectoryTest extends PHPUnit_Framework_TestCase {
	const S = DIRECTORY_SEPARATOR;

	/**
	 * @dataProvider joiningPathsProvider
	 */
	public function testJoiningPaths($args) {
		$arguments = func_get_args();
		$expected  = array_pop($arguments);

		$this->assertEquals(str_replace('/', self::S, $expected), call_user_func_array('sly_Util_Directory::join', $arguments));
	}

	public function joiningPathsProvider() {
		return array(
			array('a', 'a'),
			array('a', 'b', 'a/b'),
			array('a', 'b', 'c', 'a/b/c'),
			array('a', 345, 'c', 'a/345/c'),
			array('foo/', '/bar/blub', 'foo/bar/blub'),
			array('foo/', '/bar\\blub', 'foo/bar/blub'),
			array('foo///', '/bar/\\blub/', 'foo/bar/blub'),
			array('\\foo/', '/bar/blub/', '/foo/bar/blub'),
			array('foo', null, '/bar/', false, 'blub', 'foo/bar/blub')
		);
	}

	/**
	 * @dataProvider normalizingPathsProvider
	 */
	public function testNormalizingPaths($input, $expected) {
		$this->assertEquals(str_replace('/', self::S, $expected), sly_Util_Directory::normalize($input));
	}

	public function normalizingPathsProvider() {
		return array(
			array(12, '12'),
			array('\\foo/', '/foo'),
			array('foo\\', 'foo'),
			array('a//b//c/\\/d/x\\/', 'a/b/c/d/x')
		);
	}

	public function testDirRecognition() {
		$here     = realpath(dirname(__FILE__));
		$testPath = sly_Util_Directory::join($here, '4986z9irugh3wiufzgeu');

		// create the instance without creating the dir

		$obj = new sly_Util_Directory($testPath);
		$this->assertFalse($obj->exists());
		$this->assertEquals((string) $obj, $testPath.' (not existing)');

		// and now let's create it

		$obj = new sly_Util_Directory($testPath, true);
		$this->assertTrue(is_dir($testPath));
		$this->assertTrue($obj->exists());
		$this->assertEquals((string) $obj, $testPath);

		$this->assertTrue(rmdir($testPath));
	}
}
