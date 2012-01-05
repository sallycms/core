<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_PersistenceTest extends sly_DatabaseTest {
	private static $pers;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$pers = sly_DB_PDO_Persistence::getInstance();
	}

	protected function getDataSetName() {
		return 'sally-demopage';
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

	public function testListTables() {
		$tables = self::$pers->listTables();
		$this->assertCount(9, $tables);
		$this->assertEquals(
			array('sly_article', 'sly_article_slice', 'sly_clang', 'sly_file', 'sly_file_category',
			'sly_registry', 'sly_slice', 'sly_slice_value', 'sly_user'),
			$tables
		);

		$this->assertTrue(self::$pers->listTables('sly_user'));
		$this->assertFalse(self::$pers->listTables('a'.uniqid()));
	}

	/**
	 * @dataProvider fetchProvider
	 */
	public function testFetch($table, $cols, $where, $order, $expected) {
		// fetch a single row
		$result = self::$pers->fetch($table, $cols, $where, $order);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider fetchProvider
	 */
	public function testMagicFetch($table, $cols, $where, $order, $_, $expected = null) {
		$expected = $expected === null ? $_ : $expected;
		$result   = self::$pers->magicFetch($table, $cols, $where, $order);

		$this->assertEquals($expected, $result);
	}

	public function fetchProvider() {
		return array(
			array('user', 'id',        array('id' => 1), null, array('id' => 1),                1),
			array('user', 'id,status', array('id' => 1), null, array('id' => 1, 'status' => 1), null),
			array('user', 'id',        null,             null, array('id' => 1),                1),
			array('user', 'id',        array('id' => 2), null, false,                           null),

			array('article', 'id', null,                                  'id DESC', array('id' => 8), 8),
			array('article', 'id', array('re_id' => 0),                   'id ASC',  array('id' => 1), 1),
			array('article', 'id', array('re_id' => 0, 'startpage' => 0), 'id ASC',  array('id' => 6), 6)
		);
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
