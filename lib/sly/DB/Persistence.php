<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
abstract class sly_DB_Persistence implements Iterator {
	/**
	 *
	 * @return sly_DB_Persistence
	 */
	public static function getInstance() {
		static $data = null;
		if ($data === null) $data = sly_Core::config()->get('DATABASE');
		return new sly_DB_PDO_Persistence($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD'], $data['NAME']);
	}

	/**
	 * Führt einen query auf der Datenbank aus, der Query kann
	 * in PDO Prepared statement syntax sein, muss aber nicht.
	 *
	 * @param string $query
	 * @param array $data
	 */
	abstract public function query($query, $data = array());

	/**
	 * inserts a data set into the database
	 *
	 * @param string $table
	 * @param array $values array('column' => $value,...)
	 *
	 * @return int affected rows
	 */
	abstract public function insert($table, $values);

	/**
	 * updates data sets in database
	 *
	 * @param string $table
	 * @param array $newValues array('column' => $value,...)
	 * @param array $where array('column' => $value,...)
	 */
	abstract public function update($table, $newValues, $where = null);

	/**
	 *
	 * @param string $table
	 * @param unknown_type $select
	 * @param unknown_type $where
	 * @param unknown_type $group
	 * @param unknown_type $order
	 * @param int $offset
	 * @param int $limit
	 * @param unknown_type $having
	 * @param unknown_type $joins
	 *
	 * @return boolean
	 */
	abstract public function select($table, $select = '*', $where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null);

	/**
	 *
	 * @param string $table
	 * @param array $where array('column' => $value,...)
	 */
	abstract public function delete($table, $where = null);

	/**
	 * Hilfsfunktion um eine Zeile zu bekommen
	 *
	 * @param string $table
	 * @param string $select
	 * @param array $where
	 * @param int $order
	 *
	 * @return array row
	 */
	abstract public function fetch($table, $select = '*', $where = null, $order = null);
	
	abstract public function listTables($find = null);
}
