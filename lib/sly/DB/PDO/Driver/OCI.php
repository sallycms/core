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
class sly_DB_PDO_Driver_OCI extends sly_DB_PDO_Driver {
	/**
	 * @return string
	 */
	public function getDSN() {
		$dsn = 'oci:host='.$this->host;
		if(!empty($this->database)) $dsn .=';dbname='.$this->database;
		return $dsn;
	}

	/**
	 * @throws sly_DB_PDO_Exception  always (not yet implemented)
	 * @param  string $name          the database name
	 * @return string
	 */
	public function getCreateDatabaseSQL($name) {
		// http://www.dba-oracle.com/oracle_create_database.htm
		throw new sly_DB_Exception('Not yet implemented, too complex.');
	}
}
