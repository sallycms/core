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
 * PDO Persistence Klasse für eine PDO-Verbindung
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
	 * @param string $driverName
	 * @param string $host
	 * @param string $login
	 * @param string $password
	 * @param string $database
	 */
	public function __construct($driverName, sly_DB_PDO_Connection $connection, $prefix = '') {
		$this->driver     = $driverName;
		$this->prefix     = $prefix;
		$this->connection = $connection;
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
		$this->triggerDBchanged();
		$sql = $this->getSQLbuilder($this->getPrefix().$table);
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
		$this->triggerDBchanged();
		$sql = $this->getSQLbuilder($this->getPrefix().$table);
		$sql->update($newValues);
		$sql->where($where);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	/**
	 * @param  string  $table
	 * @param  array   $newValues
	 * @param  mixed   $where
	 * @param  boolean $transactional
	 * @return int
	 */
	public function replace($table, $newValues, $where, $transactional = false) {
		$this->triggerDBchanged();
		if ($transactional) {
			return $this->transactional(array($this, 'replaceHelper'), array($table, $newValues, $where));
		}
		else {
			$this->replaceHelper($table, $newValues, $where);
		}
	}

	protected function replaceHelper($table, $newValues, $where) {
		$count = $this->magicFetch($table, 'COUNT(*)', $where);

		if ($count == 0) {
			return $this->insert($table, array_merge($where, $newValues));
		}
		else {
			return $this->update($table, $newValues, $where);
		}
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
		$sql = $this->getSQLbuilder($this->getPrefix().$table);
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
		$this->triggerDBchanged();
		$sql = $this->getSQLbuilder($this->getPrefix().$table);
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
	public function getPrefix() {
		return $this->prefix;
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
	 * @return sly_DB_PDO_Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * @return PDO
	 */
	public function getPDO() {
		return $this->connection->getPDO();
	}

	/**
	 * @param  mixed $str
	 * @param  int   $paramType
	 * @return string
	 */
	public function quote($str, $paramType = PDO::PARAM_STR) {
		return $this->getPDO()->quote($str, $paramType);
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

	/**
	 * Check if there is an active transaction
	 *
	 * Note that this only means that there was a transaction started by using
	 * the dedicated API methods. This does *not* detect transactions started by
	 * direct queries or other PDO wrappers.
	 *
	 * @return boolean  true if a transaction is running, else false
	 */
	public function isTransRunning() {
		return $this->connection->isTransRunning();
	}

	public function transactional($callback, array $params = array()) {
		$ownTrx = !$this->isTransRunning();

		if ($ownTrx) {
			$this->beginTransaction();
		}

		try {
			$return = call_user_func_array($callback, $params);

			if ($ownTrx) {
				$this->commit();
			}

			return $return;
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$this->rollBack();
			}

			throw $e;
		}
	}

	/*
	 The following three methods exist just to make using transactions less
	 painful when you need to call protected stuff and hence cannot use an
	 anonymous function in PHP <5.4.
	 */

	public function beginTrx() {
		if ($this->isTransRunning()) {
			return false;
		}

		$this->beginTransaction();

		return true;
	}

	public function commitTrx($flag) {
		if ($flag) {
			$this->commit();
		}
	}

	public function rollBackTrx($flag, Exception $e = null) {
		if ($flag) {
			$this->rollBack();
		}

		if ($e) {
			throw $e;
		}
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
	public function getSQLbuilder($table) {
		$classname = 'sly_DB_PDO_SQLBuilder_'.strtoupper($this->driver);
		return new $classname($this->connection->getPDO(), $table);
	}

	/**
	 * Gibt alle resultierenden Zeilen zurück.
	 *
	 * @param  const $fetchStyle
	 * @param  mixed $fetchArgument
	 * @return array
	 */
	public function all($fetchStyle = PDO::FETCH_ASSOC, $fetchArgument = null) {
		if ($fetchStyle === PDO::FETCH_ASSOC) {
			return $this->statement->fetchAll($fetchStyle);
		}

		return $this->statement->fetchAll($fetchStyle, $fetchArgument);
	}

	protected function triggerDBchanged() {
		sly_Core::getContainer()->getDispatcher()->notify('SLY_DB_PDO_PERSISTANCE_CHANGED');
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
		if ($this->currentRow !== null) {
			throw new sly_DB_PDO_Exception('Über ein PDO-Resultset kann nicht mehrfach iteriert werden!');
		}
	}

	///@endcond
}
