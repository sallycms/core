<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_FunctionsTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider  slyMakeArrayProvider
	 */
	public function testSlyMakeArray($val, $expected) {
		$this->assertEquals($expected, sly_makeArray($val));
	}

	public function slyMakeArrayProvider() {
		return array(
			array(null,         array()),
			array(1,            array(1)),
			array(true,         array(true)),
			array(false,        array(false)),
			array(array(),      array()),
			array(array(1,2,3), array(1,2,3)),
		);
	}

	public function testQueryString() {
		$this->assertEquals('&foo=bar', sly_Util_HTTP::queryString(array('foo' => 'bar'), '&'));
		$this->assertEquals('&foo=%C3%9F%24', sly_Util_HTTP::queryString(array('foo' => 'ß$'), '&'));
		$this->assertEquals('foo=bar', 'foo=bar');
	}

	/**
	 * @dataProvider  slySettypeProvider
	 */
	public function testSlySettype($var, $type, $expected) {
		$this->assertEquals($expected, sly_settype($var, $type));
	}

	public function slySettypeProvider() {
		return array(
			array(1,     'int',    (int)     1),
			array(1,     'string', (string)  1),
			array('foo', 'int',    (int)     'foo'),
			array('1',   'bool',   (boolean) '1'),
			array('a',   'array',  (array)   'a'),
			array(null,  'int',    (int)     null),
			array(null,  'raw',    null),
			array(null,  '',       null)
		);
	}

	/**
	 * @dataProvider  slyArrayReplaceProvider
	 */
	public function testSlyArrayReplace($list, $old, $new, $expected) {
		$this->assertEquals($expected, sly_arrayReplace($list, $old, $new));
	}

	public function slyArrayReplaceProvider() {
		return array(
			array(array(),        1, 2,   array()),
			array(array(1),       1, 2,   array(2)),
			array(array(3),       1, 2,   array(3)),
			array(array(3,1,3,1), 1, 2,   array(3,2,3,2)),
			array(array(3,'1',2), 1, '2', array(3,'2',2)),
			array(array(3,'1',2), 2, 2,   array(3,'1',2)),
			array(array(3,'1',2), 2, '2', array(3,'1',2)),
			array(array(1,'1',2), 1, '2', array('2','2',2)),
			array(array(1,'1',2), 4, '5', array(1,'1',2)),
		);
	}

	/**
	 * @dataProvider  slyArrayDeleteProvider
	 */
	public function testSlyArrayDelete($list, $delete, $expected) {
		$this->assertEquals($expected, sly_arrayDelete($list, $delete));
	}

	public function slyArrayDeleteProvider() {
		return array(
			array(array(),          2,   array()),
			array(array(3),         2,   array(3)),
			array(array(3,1,3,1),   1,   array(0 => 3, 2 => 3)),
			array(array(3,'1',2),   '2', array(0 => 3, 1 => '1')),
			array(array(3,'1',2),   2,   array(0 => 3, 1 => '1')),
			array(array('1',3,'1'), 1,   array(1 => 3)),
		);
	}
}
