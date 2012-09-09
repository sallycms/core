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
		$this->assertSame($expected, sly_makeArray($val));
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
		$this->assertSame('&foo=bar', sly_Util_HTTP::queryString(array('foo' => 'bar'), '&'));
		$this->assertSame('&foo=%C3%9F%24', sly_Util_HTTP::queryString(array('foo' => 'ß$'), '&'));
		$this->assertSame('foo=bar', 'foo=bar');
	}

	/**
	 * @dataProvider  slySettypeProvider
	 */
	public function testSlySettype($var, $type, $expected) {
		$this->assertSame($expected, sly_settype($var, $type));
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
	 * @depends  testSlySettype
	 */
	public function testSlySetArraytype() {
		$data = array('a' => 1, 'b' => 12.34, 'c' => 'foo');

		$this->assertSame(1,        sly_setarraytype($data, 'a', 'int'));
		$this->assertSame(1.0,      sly_setarraytype($data, 'a', 'double'));
		$this->assertSame(0,        sly_setarraytype($data, 'c', 'int'));
		$this->assertSame(array(1), sly_setarraytype($data, 'a', 'array'));

		// if $key is not found, $default should not be casted
		$this->assertSame('dummy', sly_setarraytype($data, 'X', 'int', 'dummy'));
	}

	/**
	 * @depends       testSlySetArraytype
	 * @dataProvider  slyGPCProvider
	 */
	public function testSlyGPC($name, $type, $default, $expected) {
		$_GET = $_POST = $_REQUEST = array(
			'a' => 1,
			'b' => 12.34,
			'c' => 'foo',
			'd' => array('mumblefoo',1,2,3),
			'e' => false,
			'g' => '1',
			'h' => '1whoops',
			'i' => 'whoops1'
		);

		$this->assertSame($expected, sly_get($name, $type, $default));
		$this->assertSame($expected, sly_post($name, $type, $default));
		$this->assertSame($expected, sly_request($name, $type, $default));
	}

	public function slyGPCProvider() {
		return array(
			// test existing elements
			array('a', 'int',    null, 1),
			array('a', 'double', null, 1.0),
			array('a', 'float',  null, 1.0),
			array('d', 'int',    null, 1), // casting an array to an int should always return 1
			array('e', 'bool',   null, false),
			array('a', 'bool',   null, true),
			array('h', 'int',    null, 1),
			array('i', 'int',    null, 0),

			// test missing elements and that the default value is never casted
			array('A', 'int', null,  null),
			array('A', 'int', 'foo', 'foo')
		);
	}

	/**
	 * @dataProvider  slyArrayReplaceProvider
	 */
	public function testSlyArrayReplace($list, $old, $new, $expected) {
		$this->assertSame($expected, sly_arrayReplace($list, $old, $new));
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
		$this->assertSame($expected, sly_arrayDelete($list, $delete));
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

	/**
	 * @depends       testSlyMakeArray
	 * @dataProvider  slyGetArrayProvider
	 */
	public function testSlyGetArray($name, $types, $default, $expected) {
		$_GET = $_POST = array(
			'a' => array(1, 2, 0, 'a', '42'),
			'b' => array(),
			'c' => array(array(), '99', 'abc'),
			'd' => array('foo', array(1, 2, 3), 23),
			'e' => array('a' => array(1), 'b' => 23, 'c' => -8, 'd' => 'foo', 'e' => '', 'f' => array('x' => 'y'))
		);

		$this->assertSame($expected, sly_getArray($name, $types, $default));
		$this->assertSame($expected, sly_postArray($name, $types, $default));
	}

	public function slyGetArrayProvider() {
		return array(
			// are all values properly casted?
			array('a', 'int',    null, array(1, 2, 0, 0, 42)),
			array('a', 'string', null, array('1', '2', '0', 'a', '42')),
			array('a', 'bool',   null, array(true, true, false, true, true)),

			// empty arrays should be no problem
			array('b', 'bool', null, array()),

			// sub-arrays should not be allowed
			array('c', 'bool',   null, array(1 => true,  2 => true)),
			array('c', 'int',    null, array(1 => 99,    2 => 0)),
			array('d', 'int',    null, array(0 => 0,     2 => 23)),
			array('d', 'string', null, array(0 => 'foo', 2 => '23')),

			// associative arrays should be possible
			array('e', 'int',  null, array('b' => 23,   'c' => -8,   'd' => 0,    'e' => 0)),
			array('e', 'bool', null, array('b' => true, 'c' => true, 'd' => true, 'e' => false)),

			// when the key was not found, the result should always be an array anway
			array('X', 'int', 12,             array(12)),
			array('X', 'int', null,           array()),
			array('X', 'int', array(1, 2, 3), array(1, 2, 3)),

			// as with sly_[get|post|request], when the default value is used,
			// it should not be casted (apart from being transformed into an array).
			array('X', 'bool', array(1, 2, 3), array(1, 2, 3))
		);
	}

	/**
	 * @depends       testSlyGetArray
	 * @dataProvider  slyRequestArrayProvider
	 */
	public function testSlyRequestArray($name, $types, $expected) {
		$_GET = array(
			'a' => array(1, 2, 0),
			'b' => array(),
			'c' => true,
			'u' => 14,
			'v' => 'value'
		);

		$_POST = array(
			'x' => 'mum',
			'y' => 'ble',
			'c' => false,
			'u' => 0,
			'v' => ''
		);

		$this->assertSame($expected, sly_requestArray($name, $types));
	}

	public function slyRequestArrayProvider() {
		return array(
			// distinct keys
			array('a', 'int',    array(1, 2, 0)),
			array('b', 'int',    array()),
			array('x', 'int',    array(0)),
			array('y', 'string', array('ble')),

			// sly_requestArray() should prefer POST data
			array('u', 'int',    array(0)),
			array('v', 'int',    array(0)),
			array('v', 'string', array('')),
			array('c', 'bool',   array(false)),
		);
	}
}
