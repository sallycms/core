<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Manages a PDO connection to a database
 *
 * This serves as a single hub for all persistence implementations. Those are
 * supposed to share their transaction status in this class.
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Connection {
	private $driver       = null;  ///< sly_DB_PDO_Driver
	private $pdo          = null;  ///< PDO
	private $transrunning = false; ///< boolean

	/**
	 * @param sly_DB_PDO_Driver $driver
	 * @param PDO               $connection
	 */
	public function __construct(sly_DB_PDO_Driver $driver, PDO $connection) {
		$this->driver = $driver;
		$this->pdo    = $connection;
	}

	/**
	 * @param PDO $pdo               PDO instance
	 * @param sly_DB_PDO_Connection  self
	 */
	public function setPDO(PDO $pdo) {
		$this->pdo = $pdo;
		return $this;
	}

	/**
	 * @return PDO  PDO instance
	 */
	public function getPDO() {
		return $this->pdo;
	}

	/**
	 * @return sly_DB_PDO_Driver
	 */
	public function getDriver() {
		return $this->driver;
	}

	/**
	 * @return boolean
	 */
	public function isTransRunning() {
		return $this->transrunning;
	}

	/**
	 * @param boolean $bool
	 */
	public function setTransRunning($bool) {
		$this->transrunning = $bool;
	}
}
