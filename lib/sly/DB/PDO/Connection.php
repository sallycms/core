<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * Stellt eine PDO Verbindung zur Datenbank her und hält sie vor.
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Connection {
	private static $instances = array(); ///< array

	private $driver       = null;  ///< string
	private $pdo          = null;  ///< PDO
	private $transrunning = false; ///< boolean

	/**
	 * @param string $driver
	 * @param string $dsn
	 * @param string $login
	 * @param string $password
	 */
	private function __construct($driver, $dsn, $login, $password) {
		$this->driver = $driver;
		$this->pdo    = new PDO($dsn, $login, $password);

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
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
	public static function getInstance($driver, $host, $login, $password, $database) {
		if (!class_exists('sly_DB_PDO_Driver_'.strtoupper($driver))) {
			throw new sly_DB_PDO_Exception('Unbekannter Datenbank-Treiber: '.$driver);
		}

		$driverClass = 'sly_DB_PDO_Driver_'.strtoupper($driver);
		$driverObj   = new $driverClass($host, $login, $password, $database);
		$dsn         = $driverObj->getDSN();

		if (empty(self::$instances[$dsn])) {
			try {
				self::$instances[$dsn] = new self($driver, $dsn, $login, $password);
			}
			catch (PDOException $e) {
				if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
					throw new sly_DB_PDO_Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
				}
				else {
					throw new sly_DB_PDO_Exception($e->getMessage(), $e->getCode());
				}
			}
		}

		return self::$instances[$dsn];
	}

	/**
	 * @return PDO instance
	 */
	public function getPDO() {
		return $this->pdo;
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
