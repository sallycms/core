<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
		if (empty($this->database)) return 'sqlite::memory:';

		$dbFile = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally/sqlite', preg_replace('#[^a-z0-9-_.,]#i', '_', $this->database).'.sq3');
		$dir    = dirname($dbFile);

		if (!sly_Util_Directory::create($dir)) {
			throw new sly_DB_PDO_Exception('Konnte Datenverzeichnis für Datenbank '.$this->database.' nicht erzeugen.');
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
