<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_PersistenceTest extends PHPUnit_Framework_TestCase {
	private static $pers;

	public static function setUpBeforeClass() {
		self::$pers = sly_DB_PDO_Persistence::getInstance();
	}

	private function assertResultSet(array $expected) {
		$this->assertEquals($expected, self::$pers->all());
	}

	public function testGetPDO() {
		$this->assertInstanceOf('PDO', self::$pers->getPDO());
		$this->assertInstanceOf('sly_DB_PDO_Connection', self::$pers->getConnection());
	}

	public function testIterator() {
		self::$pers->query('SELECT 1,? UNION SELECT 2,? UNION SELECT 3,?', array('foo', 'bar', 'baz'));

		$idx      = 0;
		$expected = array(
			array(1 => 1, 'foo' => 'foo'),
			array(1 => 2, 'foo' => 'bar'),
			array(1 => 3, 'foo' => 'baz')
		);

		// iterate once

		foreach (self::$pers as $row) {
			$this->assertEquals($expected[$idx], $row);
			++$idx;
		}

		$this->assertCount($idx, $expected);
	}

	public function testGetAll() {
		self::$pers->query('SELECT 1,? UNION SELECT 2,? UNION SELECT 3,?', array('foo', 'bar', 'baz'));

		$expected = array(
			array(1 => 1, 'foo' => 'foo'),
			array(1 => 2, 'foo' => 'bar'),
			array(1 => 3, 'foo' => 'baz')
		);

		$this->assertEquals($expected, self::$pers->all());
	}

	/**
	 * @depends           testIterator
	 * @expectedException sly_DB_PDO_Exception
	 */
	public function testIteratorRewind() {
		self::$pers->query('SELECT 1,? UNION SELECT 2,? UNION SELECT 3,?', array('foo', 'bar', 'baz'));

		foreach (self::$pers as $row) { /* ... */ }
		foreach (self::$pers as $row) { /* ... */ } // should throw an exception
	}

	/**
	 * @depends testIterator
	 * @depends testGetAll
	 */
	public function testQuery() {
		// most primitive queries
		$this->assertTrue(self::$pers->query('SELECT 1'));
		$this->assertResultSet(array(array('1' => '1')));

		// simple placeholders
		$this->assertTrue(self::$pers->query('SELECT 1,?', array('test')));
		$this->assertResultSet(array(array('1' => '1', 'test' => 'test')));

		// named placeholders
		$this->assertTrue(self::$pers->query('SELECT 1,:foo', array('foo' => 'testX')));
		$this->assertResultSet(array(array('1' => '1', 'testX' => 'testX')));
	}
}
