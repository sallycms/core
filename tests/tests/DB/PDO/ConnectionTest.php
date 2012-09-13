<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_ConnectionTest extends PHPUnit_Framework_TestCase {
	private static $connection;

	public static function setUpBeforeClass() {
		self::$connection = sly_DB_PDO_Persistence::getInstance()->getConnection();
	}

	public function testGetPDO() {
		$this->assertInstanceOf('PDO', self::$connection->getPDO());
	}

	public function testTransRunning() {
		self::$connection->setTransRunning(false);
		$this->assertFalse(self::$connection->isTransRunning());

		self::$connection->setTransRunning(true);
		$this->assertTrue(self::$connection->isTransRunning());

		self::$connection->setTransRunning(false);
	}
}
