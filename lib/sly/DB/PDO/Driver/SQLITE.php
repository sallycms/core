<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
class sly_DB_PDO_Driver_SQLITE extends sly_DB_PDO_Driver {
	/**
	 * @throws sly_DB_PDO_Exception  when the database file could not be created
	 * @return string
	 */
	public function getDSN() {
		if (empty($this->config['path'])) return 'sqlite::memory:';

		$dir    = SLY_DATAFOLDER;
		$dbFile = sly_Util_Directory::join($dir, '/projectdb', preg_replace('#[^a-z0-9-_.,]#i', '_', $this->config['path']).'.sq3');

		if (!sly_Util_Directory::create($dir)) {
			throw new sly_DB_PDO_Exception('Konnte Datenverzeichnis für Datenbank '.$dbFile.' nicht erzeugen.');
		}

		return 'sqlite:'.$dbFile;
	}

	/**
	 * @throws sly_DB_PDO_Exception  always
	 * @param  string $name          the database name
	 * @throws sly_DB_PDO_Exception  always
	 */
	public function getCreateDatabaseSQL($name) {
		throw new sly_DB_PDO_Exception('Creating databases by SQL is not meaningful in SQLite.');
	}

	/**
	 * @return array
	 */
	public function getVersionConstraints() {
		return array(
			sly_Util_Requirements::OK      => '3.0',
			sly_Util_Requirements::WARNING => '2.0'
		);
	}
}
