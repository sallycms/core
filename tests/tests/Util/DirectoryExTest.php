<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Extended tests for the directory utility. This class exists so we can easily
 * setup the environment (setUpBeforeClass) and still have multiple test methods.
 */
class sly_Util_DirectoryExTest extends PHPUnit_Framework_TestCase {
	const S = DIRECTORY_SEPARATOR;

	static $here = '';

	public static function setUpBeforeClass() {
		$here = realpath(dirname(__FILE__));
		self::$here = $here;

		mkdir($here.'/tmp/foo/bar', 0777, true);
		mkdir($here.'/tmp/.blafasel/xy', 0777, true);
		mkdir($here.'/tmp/child', 0777, true);

		touch($here.'/tmp/foo/.htaccess');
		touch($here.'/tmp/foo/testfile.txt');
		touch($here.'/tmp/readme');
		touch($here.'/tmp/.ignoreme');
		touch($here.'/tmp/test');
		touch($here.'/tmp/helloworld');
		touch($here.'/tmp/.blafasel/list.php');
	}

	public static function tearDownAfterClass() {
		$here = self::$here;

		unlink($here.'/tmp/foo/.htaccess');
		unlink($here.'/tmp/foo/testfile.txt');
		unlink($here.'/tmp/readme');
		unlink($here.'/tmp/.ignoreme');
		unlink($here.'/tmp/test');
		unlink($here.'/tmp/helloworld');
		unlink($here.'/tmp/.blafasel/list.php');

		rmdir($here.'/tmp/.blafasel/xy');
		rmdir($here.'/tmp/.blafasel');
		rmdir($here.'/tmp/foo/bar');
		rmdir($here.'/tmp/foo');
		rmdir($here.'/tmp/child');
		rmdir($here.'/tmp');
	}

	public function testGetRelative() {
		$result   = sly_Util_Directory::getRelative(__FILE__, SLY_BASE);
		$expected = str_replace('/', self::S, 'tests/tests/Util/DirectoryExTest.php');

		$this->assertEquals($expected, $result);
	}

	// listPlain($files = true, $directories = true, $dotFiles = false, $absolute = false, $sortFunction = 'natsort')
	public function testListPlainDirectory() {
		$obj = new sly_Util_Directory(self::$here.'/tmp');

		$this->assertEquals(array('child', 'foo'), $obj->listPlain(false, true, false, false, 'sort'));
		$this->assertEquals(array('.blafasel', 'child', 'foo'), $obj->listPlain(false, true, true, false, 'sort'));
		$this->assertEquals(array('foo', 'child'), $obj->listPlain(false, true, false, false, 'rsort'));
	}

	public function testListPlainFiles() {
		// empty directory

		$obj = new sly_Util_Directory(self::$here.'/tmp/child');
		$this->assertEquals(array(), $obj->listPlain(true, false, false, false, ''));

		// directory with files

		$obj = new sly_Util_Directory(self::$here.'/tmp');
		$this->assertEquals(array('helloworld', 'readme', 'test'), $obj->listPlain(true, false, false, false, 'sort'));
		$this->assertEquals(array('.ignoreme', 'helloworld', 'readme', 'test'), $obj->listPlain(true, false, true, false, 'sort'));
		$this->assertEquals(array('child', 'foo', 'helloworld', 'readme', 'test'), $obj->listPlain(true, true, false, false, 'sort'));
		$this->assertEquals(array('.blafasel', '.ignoreme', 'child', 'foo', 'helloworld', 'readme', 'test'), $obj->listPlain(true, true, true, false, 'sort'));

		// and finally one test for absolute paths

		$prefix   = self::$here.self::S.'tmp'.self::S;
		$expected = array($prefix.'.blafasel', $prefix.'.ignoreme', $prefix.'child', $prefix.'foo', $prefix.'helloworld', $prefix.'readme', $prefix.'test');

		$this->assertEquals($expected, $obj->listPlain(true, true, true, true, 'sort'));
	}

	// listRecursive($dotFiles = false, $absolute = false)
	public function testListRecursive() {
		$obj = new sly_Util_Directory(self::$here.'/tmp');
		$s   = self::S;
		$p   = self::$here.$s.'tmp'.$s;

		$expected = array('foo'.$s.'testfile.txt', 'helloworld', 'readme', 'test');
		$this->assertEquals($expected, $obj->listRecursive(false, false));

		$expected = array($p.'foo'.$s.'testfile.txt', $p.'helloworld', $p.'readme', $p.'test');
		$this->assertEquals($expected, $obj->listRecursive(false, true));

		$expected = array('.blafasel'.$s.'list.php', '.ignoreme', 'foo'.$s.'.htaccess', 'foo'.$s.'testfile.txt', 'helloworld', 'readme', 'test');
		$this->assertEquals($expected, $obj->listRecursive(true, false));

		$expected = array($p.'.blafasel'.$s.'list.php', $p.'.ignoreme', $p.'foo'.$s.'.htaccess', $p.'foo'.$s.'testfile.txt', $p.'helloworld', $p.'readme', $p.'test');
		$this->assertEquals($expected, $obj->listRecursive(true, true));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testUnknownSortFunction() {
		$obj = new sly_Util_Directory(self::$here.'/tmp');
		$obj->listPlain(true, true, true, true, 'dfsjhiu34zg5ui324r');
	}
}
