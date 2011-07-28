<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
abstract class sly_DB_PDO_Driver {
	protected $host;
	protected $login;
	protected $password;
	protected $database;

	public static $drivers = array('mysql', 'oci', 'pgsql', 'sqlite');

	public function __construct($host, $login, $password, $database) {
		$this->host     = $host;
		$this->login    = $login;
		$this->password = $password;
		$this->database = $database;
	}

	public static function getAvailable() {
		return array_intersect(self::$drivers, PDO::getAvailableDrivers());
	}

	abstract public function getDSN();
}
