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
 * PDO Persintence Klasse für eine PDO-Verbindung
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Persistence extends sly_DB_Persistence {
	protected $driver;           ///< string
	private $connection = null;  ///< sly_DB_PDO_Connection
	private $statement  = null;  ///< PDOStatement
	private $currentRow = null;  ///< int

	/**
	 * @param string $driver
	 * @param string $host
	 * @param string $login
	 * @param string $password
	 * @param string $database
	 */
	public function __construct($driver, $host, $login, $password, $database = null) {
		$this->driver     = $driver;
		$this->connection = sly_DB_PDO_Connection::getInstance($driver, $host, $login, $password, $database);
	}

	/**
	 * @throws sly_DB_PDO_Exception
	 * @param  string $query
	 * @param  array  $data
	 * @return boolean               always true
	 */
	public function query($query, $data = array()) {
		try {
			$this->currentRow = null;
			$this->statement  = null;
			$this->statement  = $this->connection->getPDO()->prepare($query);

			if ($this->statement->execute($data) === false) {
				$this->error();
			}
		}
		catch (PDOException $e) {
			$this->error();
		}

		return true;
	}

	/**
	 * Execute a single statement
	 *
	 * Use this method on crappy servers that fuck up serialized data when
	 * importing a dump.
	 *
	 * @throws sly_DB_PDO_Exception
	 * @param  string $query
	 * @return int
	 */
	public function exec($query) {
		$retval = $this->connection->getPDO()->exec($query);

		if ($retval === false) {
			throw new sly_DB_PDO_Exception('Es trat ein Datenbankfehler auf!');
		}

		return $retval;
	}

	/**
	 * @param  string $table
	 * @param  array  $values
	 * @return int
	 */
	public function insert($table, $values) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->insert($values);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	/**
	 * @param  string $table
	 * @param  array  $newValues
	 * @param  mixed  $where
	 * @return int
	 */
	public function update($table, $newValues, $where = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->update($newValues);
		$sql->where($where);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	/**
	 * @param  string $table
	 * @param  string $select
	 * @param  mixed  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @param  string $joins
	 * @return boolean         always true
	 */
	public function select($table, $select = '*', $where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->select($select);

		if ($where) $sql->where($where);
		if ($group) $sql->group($group);
		if ($having) $sql->having($having);
		if ($order) $sql->order($order);
		if ($offset) $sql->offset($offset);
		if ($limit) $sql->limit($limit);
		if ($joins) $sql->joins($joins);

		return $this->query($sql->to_s(), $sql->bind_values());
	}

	/**
	 * Delete rows from DB
	 *
	 * @param  string $table  table name without system prefix
	 * @param  array  $where  a hash (column => value ...)
	 * @return int            affected rows
	 */
	public function delete($table, $where = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->delete($where);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	/**
	 * @param  string $find
	 * @return mixed         boolean if $find was set, else an array
	 */
	public function listTables($find = null) {
		$sql = $this->getSQLbuilder('');
		$sql->list_tables();
		$this->query($sql->to_s(), $sql->bind_values());

		$tables = array();

		foreach ($this as $row) {
			$values = array_values($row);
			$tables[] = reset($values);
		}

		if (is_string($find)) {
			return in_array($find, $tables);
		}

		return $tables;
	}

	/**
	 * @return int
	 */
	public function lastId() {
		return intval($this->connection->getPDO()->lastInsertId());
	}

	/**
	 * @return int
	 */
	public function affectedRows() {
		return $this->statement ? $this->statement->rowCount() : 0;
	}

	/**
	 * @return string
	 */
	private static function getPrefix() {
		static $prefix = null;

		if ($prefix === null) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		}

		return $prefix;
	}

	/**
	 * @param  string $table
	 * @param  string $select
	 * @param  mixed  $where
	 * @param  string $order
	 * @return array
	 */
	public function fetch($table, $select = '*', $where = null, $order = null) {
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();

		if ($this->statement) {
			$this->statement->closeCursor();
		}

		return $data;
	}

	/**
	 * @param  string $table
	 * @param  string $select
	 * @param  mixed  $where
	 * @param  string $order
	 * @return mixed           false if nothing found, an array if more than one column has been fetched, else the selected value (single column)
	 */
	public function magicFetch($table, $select = '*', $where = null, $order = null) {
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();

		if ($this->statement) {
			$this->statement->closeCursor();
		}

		if ($data === false) {
			return false;
		}

		if (count($data) == 1) {
			$ret = array_values($data);
			return $ret[0];
		}

		return $data;
	}

	/**
	 * @param  mixed $str
	 * @param  int   $paramType
	 * @return string
	 */
	public function quote($str, $paramType = PDO::PARAM_STR) {
		return $this->connection->getPDO()->quote($str, $paramType);
	}

	// =========================================================================
	// TRANSACTIONS
	// =========================================================================

	/**
	 * Transaktion starten
	 */
	public function beginTransaction() {
		$this->connection->getPDO()->beginTransaction();
		$this->connection->setTransRunning(true);
	}

	/**
	 * Transaktion beenden
	 */
	public function commit() {
		$this->connection->getPDO()->commit();
		$this->connection->setTransRunning(false);
	}

	/**
	 * Transaktion zurücknehmen
	 */
	public function rollBack() {
		$this->connection->getPDO()->rollBack();
		$this->connection->setTransRunning(false);
	}

	// =========================================================================
	// ERROR UND LOGGING
	// =========================================================================

	/**
	 * @throws sly_DB_PDO_Exception
	 */
	protected function error() {
		$message = 'Es trat ein Datenbank-Fehler auf: ';
		throw new sly_DB_PDO_Exception($message.'Fehlercode: '. $this->getErrno() .' '.$this->getError());
	}

	/**
	 * Gibt die letzte Fehlermeldung zurück.
	 *
	 * @return string  die letzte Fehlermeldung
	 */
	protected function getError() {
		if (!$this->statement) {
			return '';
		}

		$info = $this->statement->errorInfo();
		return $info[2]; // Driver-specific error message.
	}

	/**
	 * Gibt den letzten Fehlercode zurück.
	 *
	 * @return int  der letzte Fehlercode oder -1, falls ein Fehler auftrat
	 */
	protected function getErrno() {
		return $this->statement ? $this->statement->errorCode() : -1;
	}

	/**
	 * @param  string $table
	 * @return sly_DB_PDO_SQLBuilder
	 */
	protected function getSQLbuilder($table) {
		$classname = 'sly_DB_PDO_SQLBuilder_'.strtoupper($this->driver);
		return new $classname($this->connection->getPDO(), $table);
	}


	// =========================================================================
	// ITERATOR-METHODEN
	// =========================================================================

	///@cond INCLUDE_ITERATOR_METHODS

	public function current() {
		return $this->currentRow;
	}

	public function next() {
		$this->currentRow = $this->statement->fetch(PDO::FETCH_ASSOC);

		if ($this->currentRow === false) {
			$this->statement->closeCursor();
			$this->statement = null;
		}
	}

	public function key() {
		return null;
	}

	public function valid() {
		if ($this->statement === null) {
			return false;
		}

		// Wurde noch gar keine Zeile geholt? Dann holen wir das hier nach.
		if ($this->currentRow === null) {
			$this->next();
		}

		return is_array($this->currentRow);
	}

	public function rewind() {
		// Ist in PDO-Statements nicht möglich!
		// Achtung also, wenn über ein Result mehrfach iteriert werden soll.

		if ($this->currentRow !== null) {
			trigger_error('Über ein PDO-Resultset kann nicht mehrfach iteriert werden!', E_USER_WARNING);
		}
	}

	///@endcond
}
