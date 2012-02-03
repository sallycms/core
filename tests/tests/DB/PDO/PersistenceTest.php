<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_PersistenceTest extends sly_BaseTest {
	private static $pers;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$pers = sly_DB_PDO_Persistence::getInstance();
	}

	protected function getDataSetName() {
		return 'sally-demopage';
	}

	private function assertResultSet(array $expected) {
		$all = self::$pers->all();
		$len = count($expected);

		$this->assertCount($len, $all);

		for ($i = 0; $i < $len; ++$i) {
			$this->assertEquals($expected[$i], $all[$i]);
		}
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

	/**
	 * @dataProvider selectProvider
	 */
	public function testSelect($expected, $table, $select, $where, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null) {
		$result = self::$pers->select($table, $select, $where, $group, $order, $offset, $limit, $having, $joins);

		$this->assertTrue($result);
		$this->assertResultSet($expected);
	}

	public function selectProvider() {
		return array(
			array(
				array(array('id' => 1)),
				'article', 'id', 'id = 1 AND clang = 5'
			),

			array(
				array(array('id' => 5)),
				'article', 'id', array('id' => 5, 'clang' => 5)
			),

			array(
				array(),
				'article', 'id', 'id = -1 AND clang = 5'
			),

			array(
				array(array('startpage' => 0), array('startpage' => 1)),
				'article', 'startpage', null, 'startpage', 'startpage'
			),

			array(
				array(array('startpage' => 1)),
				'article', 'startpage', array('startpage' => 1), 'startpage'
			),

			array(
				array(array('startpage' => 1)),
				'article', 'startpage', null, 'startpage', 'startpage', 1
			),

			array(
				array(array('id' => 1)),
				'article', 'id', array('clang' => 5), null, 'id', 0, 1
			),

			array(
				array(array('id' => 2)),
				'article', 'id', array('clang' => 5), null, 'id', 1, 1
			),

			array(
				array(array('id' => 2), array('id' => 3)),
				'article', 'id', array('clang' => 5), null, 'id', 1, 2
			),
		);
	}
}
