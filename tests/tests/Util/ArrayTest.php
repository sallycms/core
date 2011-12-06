<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_ArrayTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		$this->obj = new sly_Util_Array(array());
	}

	public function testPathParsing() {
		$this->obj->set('FOO', 'BAR');

		$this->assertEquals('BAR', $this->obj->get('FOO'));
		$this->assertEquals('BAR', $this->obj->get('FOO/'));
		$this->assertEquals('BAR', $this->obj->get('/FOO'));
		$this->assertEquals('BAR', $this->obj->get('/FOO/'));
		$this->assertEquals(array('FOO' => 'BAR'), $this->obj->get(''));
		$this->assertEquals(array('FOO' => 'BAR'), $this->obj->get('//'));

		$this->obj->set('bla/bar', 1);
		$this->obj->set('bla///muh/', 2);

		$this->assertEquals(array('bar' => 1, 'muh' => 2), $this->obj->get('bla'));
		$this->assertEquals(2, $this->obj->get('bla/muh'));
	}

	public function testGetting() {
		$this->obj->set('muh', array('hallo' => 'welt', 'xy' => array('abc' => 'yes')));

		// easy gets

		$this->assertEquals('yes', $this->obj->get('muh/xy/abc'));
		$this->assertEquals(array('abc' => 'yes'), $this->obj->get('muh/xy'));

		// remove part of the tree and see if the rest remained correctly

		$this->obj->remove('muh///xy/');
		$this->assertEquals(array('hallo' => 'welt'), $this->obj->get('muh'));

		// hard-core test the path parsing

		$this->obj->set('muh/test/loki', 5);
		$this->assertEquals(array('loki' => 5), $this->obj->get('muh/test'));
		$this->assertEquals(array('loki' => 5), $this->obj->get('/muh///test///'));
		$this->assertEquals(array('loki' => 5), $this->obj->get('///muh/test'));
	}

	public function testHas() {
		$this->obj->set('FOO', 'BAR');
		$this->assertTrue($this->obj->has('FOO'));
		$this->assertFalse($this->obj->has('FOO2'));

		$this->obj->remove('FOO');
		$this->obj->set('FOO2', 'BAR');

		$this->assertTrue($this->obj->has('FOO2'));
		$this->assertFalse($this->obj->has('FOO'));
	}

	public function testNumericPath() {
		$this->obj->set(5, 5);
		$this->assertEquals(5, $this->obj->get(5));
	}

	public function testWiping() {
		$this->obj->remove('////');
		$this->assertEquals(array(), $this->obj->get(''));
	}

	public function testOverwritingValue() {
		$this->obj->set('FOO', 'BAR');
		$this->obj->set('FOO', 'BAR_NEU');

		$this->assertEquals('BAR_NEU', $this->obj->get('FOO'));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testForbidArrayfication() {
		$this->obj->set('FOO', 'BAR');
		$this->obj->set('FOO/subkey', 'BAR');
	}

	/**
	 * @dataProvider typeSafetyProvider
	 */
	public function testTypeSafety($value, $type) {
		$this->obj->set('hallo', $value);

		if ($type === 'stdClass') {
			$this->assertInstanceOf($type, $this->obj->get('hallo'));
		}
		else {
			$this->assertInternalType($type, $this->obj->get('hallo'));
		}
	}

	public function typeSafetyProvider() {
		return array(
			array(12.2, PHPUnit_Framework_Constraint_IsType::TYPE_FLOAT),
			array(1, PHPUnit_Framework_Constraint_IsType::TYPE_INT),
			array(true, PHPUnit_Framework_Constraint_IsType::TYPE_BOOL),
			array(false, PHPUnit_Framework_Constraint_IsType::TYPE_BOOL),
			array(new stdClass(), 'stdClass')
		);
	}

	public function testAny() {
		$predicate = create_function('$x', 'return $x % 2 == 0;'); // match even

		$this->assertTrue(sly_Util_Array::any($predicate,  array(1,2,3)));
		$this->assertTrue(sly_Util_Array::any($predicate,  array(2)));
		$this->assertFalse(sly_Util_Array::any($predicate, array(1)));
		$this->assertFalse(sly_Util_Array::any($predicate, array()));
	}

	public function testAnyAll() {
		$predicate = create_function('$x', 'return true;'); // match all

		$this->assertTrue(sly_Util_Array::any($predicate,  array(null)));
		$this->assertFalse(sly_Util_Array::any($predicate, array()));
	}

	public function testAnyNone() {
		$predicate = create_function('$x', 'return false;'); // match none

		$this->assertFalse(sly_Util_Array::any($predicate,  array(null)));
		$this->assertFalse(sly_Util_Array::any($predicate, array()));
	}
}
