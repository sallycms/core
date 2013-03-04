<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_DumpTest extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$dir = dirname(__FILE__);

		file_put_contents("$dir/dumpA.sql", "-- Sally Database Dump Version 0.6\r\n-- Prefix foo_");
		file_put_contents("$dir/dumpB.sql", "## Sally Database Dump Version 1\n");
		file_put_contents("$dir/dumpC.sql", "");
		file_put_contents("$dir/dumpD.sql", "-- Sally Database Dump Version 0.6\n-- Prefix "); // empty prefix!

		// login the dummy user
		$service = sly_Service_Factory::getUserService();
		$user    = $service->findById(SLY_TESTING_USER_ID);
		$service->setCurrentUser($user);
	}

	public static function tearDownAfterClass() {
		$dir = dirname(__FILE__);

		unlink("$dir/dumpA.sql");
		unlink("$dir/dumpB.sql");
		unlink("$dir/dumpC.sql");
		unlink("$dir/dumpD.sql");
		@unlink("$dir/content.sql");
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testConstructor() {
		new sly_DB_Dump('nonexisting.sql');
	}

	/**
	 * @dataProvider dumpProvider
	 */
	public function testGetProperties($dump, $version, $prefix, $count) {
		$d = new sly_DB_Dump(dirname(__FILE__).'/'.$dump);
		$this->assertEquals($version, $d->getVersion());
		$this->assertEquals($prefix, $d->getPrefix());
		$this->assertCount($count, $d->getHeaders());
	}

	public function dumpProvider() {
		return array(
			array('dumpA.sql', '0.6', 'foo_', 2),
			array('dumpB.sql', '1', false, 1),
			array('dumpC.sql', false, false, 0),
			array('dumpD.sql', '0.6', '', 2)
		);
	}

	public function testGetContent() {
		$file    = dirname(__FILE__).'/content.sql';
		$content = 'INSERT INTO foo (); SELECT foo FROM bar; INSERT INTO bar ();';

		file_put_contents($file, $content);

		$dump = new sly_DB_Dump($file);
		$this->assertSame($content, $dump->getContent());
		$this->assertCount(3, $dump->getQueries());
	}

	/**
	 * @depends  testGetContent
	 */
	public function testGetChunkedContent() {
		$file  = dirname(__FILE__).'/content.sql';
		$count = 20000;

		@unlink($file);

		// looks harmless, but is in fact specifically built to trigger re-reads of the dump file
		for ($i = 0; $i < $count; ++$i) {
			file_put_contents(
				$file,
				"INSERT INTO foo (a, b, c) VALUES (12355, 5658, `9123`, 'foo bar \\' hello world'); ".
				"/* i am a comment that is really long, so it hopefully creates a second fread() */\n",
				FILE_APPEND
			);
		}

		$dump = new sly_DB_Dump($file);
		$this->assertCount($count, $dump->getQueries());
	}

	/**
	 * @dataProvider  replacePrefixProvider
	 */
	public function testReplacePrefix($query, $expected) {
		$dump = new sly_DB_Dump(dirname(__FILE__).'/dumpA.sql');
		$dump->getPrefix(); // parse the file header
		$this->assertSame($expected, $dump->replacePrefix($query));
	}

	public function replacePrefixProvider() {
		return array(
			array('INSERT INTO foo_table WHERE 1',       'INSERT INTO sly_table WHERE 1'),
			array('INSERT into `foo_table` WHERE 1',     'INSERT into `sly_table` WHERE 1'),
			array('INSERT INTO `foo_foo_table` WHERE 1', 'INSERT INTO `sly_foo_table` WHERE 1'),
			array('DROP TABLE `foo_table`',              'DROP TABLE `sly_table`'),
			array('CREATE table `foo_table`',            'CREATE table `sly_table`'),
		);
	}

	/**
	 * @dataProvider  replaceVariablesProvider
	 */
	public function testReplaceVariables($query, $expected) {
		$this->assertSame($expected, sly_DB_Dump::replaceVariables($query));
	}

	public function replaceVariablesProvider() {
		return array(
			array('SELECT %USER% FROM table',         'SELECT admin FROM table'),
			array('SELECT %TABLE_PREFIX% FROM table', 'SELECT sly_ FROM table'),
		);
	}

	public function testReplaceTimeVariable() {
		$qry = 'SELECT %TIME% FROM foo';
		$exp = 'SELECT '.time().' FROM foo';

		$this->assertSame($exp, sly_DB_Dump::replaceVariables($qry));
	}
}
