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
class sly_DB_PDO_Driver_MYSQL extends sly_DB_PDO_Driver {
	/**
	 * @return string
	 */
	public function getDSN() {
		if (strpos($this->host, '/') !== false) {
			$dsn = 'mysql:unix_socket='.$this->host;
		}
		else {
			$parts = explode(':', $this->host);

			if (count($parts) === 1) {
				$dsn = 'mysql:host='.$this->host;
			}
			else {
				$dsn = sprintf('mysql:host=%s;port=%s', $parts[0], $parts[1]);
			}
		}

		if (!empty($this->database)) {
			$dsn .= ';dbname='.$this->database;
		}

		return $dsn.';charset=utf8';
	}

	/**
	 * @param  string $name  the database name
	 * @return string
	 */
	public function getCreateDatabaseSQL($name) {
		return 'CREATE DATABASE `'.$name.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
	}

 	/**
 	 * @return array
 	 */
	public function getPDOOptions() {
		// http://php.net/manual/en/ref.pdo-mysql.connection.php
		if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
			return array();
		}
		else {
			return array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
		}
	}

 	/**
 	 * @return array
 	 */
	public function getPDOAttributes() {
		return array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true);
	}

	/**
	 * @return array
	 */
	public function getVersionConstraints() {
		return array(
			sly_Util_Requirements::OK      => '5.1',
			sly_Util_Requirements::WARNING => '5.1'
		);
	}
}
