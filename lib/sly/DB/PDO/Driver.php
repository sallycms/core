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
abstract class sly_DB_PDO_Driver {
	protected $config;     ///< array

	public static $drivers = array('mysql', 'oci', 'pgsql', 'sqlite'); ///< array

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->config = $array;
	}

	/**
	 * @return array
	 */
	public function getPDOOptions() {
		return array();
	}

	/**
	 * @return array
	 */
	public function getPDOAttributes() {
		return array();
	}

	/**
	 * @return array
	 */
	public static function getAvailable() {
		return array_intersect(self::$drivers, PDO::getAvailableDrivers());
	}

	/**
	 * @return string
	 */
	abstract public function getDSN();

	/**
	 * @return array
	 */
	abstract public function getVersionConstraints();

	/**
	 * @param  string $name  the database name
	 * @return string
	 */
	abstract public function getCreateDatabaseSQL($name);
}
