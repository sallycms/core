<?php
/*
 * Copyright (c) 2016, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace sly\Database;

use sly_DB_Persistence;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\DBALException;
use sly_DB_Exception;

/**
 * Persistence Klasse für eine Datenbank Verbindung
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class Persistence implements sly_DB_Persistence {
	private $connection = null;  ///< sly\Database\Connection
	private $statement  = null;  ///< PDOStatement
	private $currentRow = null;  ///< int

	/**
	 * @param sly\Database\Connection $connection
	 * @param string $prefix
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @throws sly_DB_PDO_Exception
	 * @param  string $query
	 * @param  array  $data
	 * @return Persistence
	 */
	public function query($query, $data = array()) {
		try {
			$this->currentRow = null;
			$this->statement  = null;
			$this->statement  = $this->connection->prepare($query);

			if ($this->statement->execute($data) === false) {
				$this->error($query);
			}
		}
		catch (ConnectionException $e) {
			throw new \sly_DB_Exception('Connection to database failed.');
		}
		catch (DBALException $e) {
			$this->error($query);
		}

		return $this;
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
		$retval = $this->connection->exec($query);

		if ($retval === false) {
			throw new sly_DB_Exception('Es trat ein Datenbankfehler auf!');
		}

		return $retval;
	}

	/**
	 * @param  string $table
	 * @param  array  $values
	 * @return int
	 */
	public function insert($table, $values) {
		$sql = $this->getSQLbuilder();
		$sql->insert($this->connection->getTable($table));
		$sql->values(array_fill_keys(array_keys($values), '?'));

		$this->query($sql->getSQL(), array_values($values));

		return $this->statement->rowCount();
	}

	/**
	 * @param  string $table
	 * @param  array  $newValues
	 * @param  mixed  $where
	 * @return int
	 */
	public function update($table, $newValues, $where = null) {
		$sql = $this->getSQLbuilder();
		$sql->update($this->connection->getTable($table));

		foreach($newValues as $column => $value) {
			$sql->set($column, '?');
			$sql->createPositionalParameter($value);
		}

		$this->where($sql, $where);

		$this->query($sql->getSQL(), array_values($sql->getParameters()));

		return $this->statement->rowCount();
	}

	/**
	 * @param  string  $table
	 * @param  array   $newValues
	 * @param  mixed   $where
	 * @param  boolean $transactional
	 * @return int
	 */
	public function replace($table, $newValues, $where, $transactional = false) {
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
		$sql    = $this->getSQLbuilder();

		$sql->select($select);
		$sql->from($sql->getConnection()->getTable($table), $table);

		$this->where($sql, $where);

		if ($group) {
			$sql->groupBy($group);
		}

		if ($having) {
			$sql->andHaving($having);
		}

		if ($order) {
			$order = explode(' ', $order);
			while(!empty($order)) {
				$column    = array_shift($order);
				$direction = array_shift($order);

				if ($column) {
					$column = trim($column, ',');
				}

				if ($direction !== null) {
					$direction = trim($direction, ',');
				}

				$sql->orderBy($column, $direction);
			}
		}

		$sql->setFirstResult($offset);
		$sql->setMaxResults($limit);

		if ($joins) {
			$sql->add('from', $join);
		}

		return $this->query($sql->getSQL(), array_values($sql->getParameters()));
	}

	/**
	 * Delete rows from DB
	 *
	 * @param  string $table  table name without system prefix
	 * @param  array  $where  a hash (column => value ...)
	 * @return int            affected rows
	 */
	public function delete($table, $where = null) {
		$sql = $this->getSQLbuilder();
		$sql->delete($this->connection->getTable($table));

		$this->where($sql, $where);

		$this->query($sql->getSQL(), array_values($sql->getParameters()));

		return $this->statement->rowCount();
	}

	/**
	 * @param  string $find
	 * @return mixed         boolean if $find was set, else an array
	 */
	public function listTables($find = null) {
		$tables = $this->connection->getSchemaManager()->listTableNames();

		if (is_string($find)) {
			return in_array($find, $tables);
		}

		return $tables;
	}

	/**
	 * @return int
	 */
	public function lastId() {
		return intval($this->connection->lastInsertId());
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
		return $this->connection->getPrefix();
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
	 * @return \sly\Database\Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * @param  mixed $str
	 * @param  int   $paramType
	 * @return string
	 */
	public function quote($str, $paramType = \PDO::PARAM_STR) {
		return $this->connection->quote($str, $paramType);
	}

	public function quoteIdentifier($identifier) {
		return $this->connection->quoteIdentifier($identifier);
	}

	protected function where(\Doctrine\DBAL\Query\QueryBuilder $sql, $where) {
		if (is_string($where)) {
			$sql->where($where);
		}
		elseif (is_array($where)) {
			foreach($where as $column => $value) {
				$sql->andWhere($column .' = ?');
				$sql->createPositionalParameter($value);
			}
		}
	}

	// =========================================================================
	// TRANSACTIONS
	// =========================================================================

	/**
	 * Transaktion starten
	 */
	public function beginTransaction() {
		$this->connection->beginTransaction();
	}

	/**
	 * Transaktion beenden
	 */
	public function commit() {
		$this->connection->commit();
	}

	/**
	 * Transaktion zurücknehmen
	 */
	public function rollBack() {
		$this->connection->rollBack();
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
		return $this->connection->getTransactionNestingLevel() > 0;
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
		catch (\Exception $e) {
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

	public function rollBackTrx($flag, \Exception $e = null) {
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
	 * @throws sly_DB_Exception
	 */
	protected function error($query) {
		$message = 'Es trat ein Datenbank-Fehler auf: ';
		$error   = $this->statement->errorInfo();
		throw new sly_DB_Exception($query.' '.$message.'Fehlercode: '. $error[0] .' '.$error[2], $error[1]);
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
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 */
	public function getSQLbuilder() {
		return $this->connection->createQueryBuilder();
	}

	/**
	 * Gibt alle resultierenden Zeilen zurück.
	 *
	 * @param  const $fetchStyle
	 * @param  mixed $fetchArgument
	 * @return array
	 */
	public function all($fetchStyle = \PDO::FETCH_ASSOC, $fetchArgument = null) {
		if ($fetchStyle === \PDO::FETCH_ASSOC) {
			return $this->statement->fetchAll($fetchStyle);
		}

		return $this->statement->fetchAll($fetchStyle, $fetchArgument);
	}

	// =========================================================================
	// ITERATOR-METHODEN
	// =========================================================================

	///@cond INCLUDE_ITERATOR_METHODS

	public function current() {
		return $this->currentRow;
	}

	public function next() {
		$this->currentRow = $this->statement->fetch(\PDO::FETCH_ASSOC);

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
			throw new sly_DB_Exception('Über ein PDO-Resultset kann nicht mehrfach iteriert werden!');
		}
	}

	///@endcond
}
