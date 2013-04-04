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
 * Stellt eine PDO Verbindung zur Datenbank her und hält sie vor.
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Connection {
	private static $instances = array(); ///< array

	private $driver       = null;  ///< sly_DB_PDO_Driver
	private $pdo          = null;  ///< PDO
	private $transrunning = false; ///< boolean

	/**
	 * @param sly_DB_PDO_Driver $driver
	 * @param string            $dsn
	 * @param string            $login
	 * @param string            $password
	 */
	private function __construct(sly_DB_PDO_Driver $driver, $dsn, $login, $password) {
		$this->driver = $driver;
		$this->pdo    = new PDO($dsn, $login, $password, $driver->getPDOOptions());

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		foreach ($driver->getPDOAttributes() as $key => $value) {
			$this->pdo->setAttribute($key, $value);
		}
	}

	/**
	 * @throws sly_DB_PDO_Exception
	 * @param  string $driverName
	 * @param  string $host
	 * @param  string $login
	 * @param  string $password
	 * @param  string $database
	 * @return sly_DB_PDO_Connection instance
	 */
	public static function getInstance($driverName, $host, $login, $password, $database) {
		$driverObj = self::getDriverInstance($driverName, $host, $login, $password, $database);
		$dsn       = $driverObj->getDSN();

		if (empty(self::$instances[$dsn])) {
			try {
				self::$instances[$dsn] = new self($driverObj, $dsn, $login, $password);
			}
			catch (PDOException $e) {
				throw new sly_DB_PDO_Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
			}
		}

		return self::$instances[$dsn];
	}

	/**
	 * @throws sly_DB_PDO_Exception
	 * @param  string $driver
	 * @param  string $host
	 * @param  string $login
	 * @param  string $password
	 * @param  string $database
	 * @return sly_DB_PDO_Connection instance
	 */
	public static function getDriverInstance($driver, $host, $login, $password, $database) {
		if (!class_exists('sly_DB_PDO_Driver_'.strtoupper($driver))) {
			throw new sly_DB_PDO_Exception('Unknown Database Driver: '.$driver);
		}

		$driverClass = 'sly_DB_PDO_Driver_'.strtoupper($driver);

		return new $driverClass($host, $login, $password, $database);
	}

	/**
	 * @return PDO instance
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
