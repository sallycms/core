<?php
/*
 * Copyright (c) 2016, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace sly\Tests\Database;

use sly_Core;
use PHPUnit_Framework_TestCase as TestCase;

class sly_Database_ConnectionTest extends TestCase {
	private static $connection;

	public static function setUpBeforeClass() {
		self::$connection = sly_Core::getContainer()->get('sly-dbal-connection');
	}

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	public function testInstance() {
		$this->assertSame(get_class(self::$connection), 'sly\\Database\\Connection');
	}

	public function testTableName() {
		$this->assertSame(self::$connection->getTable('foobar'), 'sly_foobar');
	}


}
