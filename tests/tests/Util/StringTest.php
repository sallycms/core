<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_StringTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider isIntegerProvider
	 */
	public function testIsInteger($value, $expected) {
		$this->assertEquals($expected ? true : false, sly_Util_String::isInteger($value));
	}

	public function isIntegerProvider() {
		return array(
			array(5, 1), array(-5, 1), array('1', 1), array('901', 1), array('-901', 1),
			array(5.1, 0), array(true, 0), array(false, 0), array(null, 0), array('01', 0), array('1.5', 0), array('- 7', 0), array('hello', 0), array('123hello', 0), array(' ', 0), array("\t", 0), array('', 0)
		);
	}

	/**
	 * @dataProvider startsWithProvider
	 */
	public function testStartsWith($a, $b, $expected) {
		$this->assertEquals($expected ? true : false, sly_Util_String::startsWith($a, $b));
	}

	public function startsWithProvider() {
		return array(
			array('', '', 1), array('hallo', '', 1), array('hallo', 'hal', 1), array('  hallo', '  hal', 1), array('1123', '1', 1), array(12, 1, 1),
			array('', 'hallo', 0), array('hallo', 'hallo123', 0), array('hallo', 'xyz', 0), array('hallo', 'H', 0), array('hallo', ' ', 0), array('  hallo', 0, 0)
		);
	}

	/**
	 * @dataProvider endsWithProvider
	 */
	public function testEndsWith($a, $b, $expected) {
		$this->assertEquals($expected ? true : false, sly_Util_String::endsWith($a, $b));
	}

	public function endsWithProvider() {
		return array(
			array('', '', 1), array('hallo', '', 1), array('hallo', 'llo', 1), array('  hallo', '  hallo', 1), array('1123', '23', 1), array(12, 2, 1),
			array('', 'hallo', 0), array('hallo', '123hallo', 0), array('hallo', 'xyz', 0), array('hallo', 'O', 0), array('hallo', ' ', 0), array('  hallo', 0, 0)
		);
	}

	/**
	 * @dataProvider strToUpperProvider
	 */
	public function testStrToUpper($input, $expected) {
		$this->assertEquals($expected, sly_Util_String::strToUpper($input));
	}

	public function strToUpperProvider() {
		return array(
			array('hallo', 'HALLO'), array('süß', 'SÜSS'), array('The answer is 42.', 'THE ANSWER IS 42.')
		);
	}

	/**
	 * @dataProvider humanImplodeProvider
	 */
	public function testHumanImplode($list, $expected) {
		$this->assertEquals($expected, sly_Util_String::humanImplode($list, ' und '));
	}

	public function humanImplodeProvider() {
		return array(
			array(array(), ''), array(array(1), '1'), array(array(1,2), '1 und 2'), array(array(1,2,3), '1, 2 und 3'), array(array(1,2,3,4), '1, 2, 3 und 4')
		);
	}

	public function testGetRandomString() {
		$this->assertTrue(strlen(sly_Util_String::getRandomString(5, 10)) >= 5);
		$this->assertTrue(strlen(sly_Util_String::getRandomString(5, 10)) <= 10);
		$this->assertTrue(strlen(sly_Util_String::getRandomString(5, 5)) == 5);
		$this->assertTrue(strlen(sly_Util_String::getRandomString(10, 5)) >= 5);
		$this->assertTrue(strlen(sly_Util_String::getRandomString(10, 5)) <= 10);

		$this->assertNotEquals(sly_Util_String::getRandomString(5, 10), sly_Util_String::getRandomString(5, 10));
	}
}
