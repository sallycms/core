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
interface sly_DB_Persistence extends Iterator {
	/**
	 * Führt einen query auf der Datenbank aus, der Query kann
	 * in PDO Prepared statement syntax sein, muss aber nicht.
	 *
	 * @param string $query
	 * @param array  $data
	 */
	public function query($query, $data = array());

	/**
	 * inserts a data set into the database
	 *
	 * @param  string $table
	 * @param  array  $values  array('column' => $value, ...)
	 * @return int             affected rows
	 */
	public function insert($table, $values);

	/**
	 * updates data sets in database
	 *
	 * @param string $table
	 * @param array  $newValues  array('column' => $value, ...)
	 * @param array  $where      array('column' => $value, ...)
	 */
	public function update($table, $newValues, $where = null);

	/**
	 * inserts or updates data sets in database
	 *
	 * @param  string  $table
	 * @param  array   $newValues
	 * @param  mixed   $where
	 * @param  boolean $transactional
	 * @return int
	 */
	public function replace($table, $newValues, $where, $transactional = false);

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
	 * @return boolean
	 */
	public function select($table, $select = '*', $where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null);

	/**
	 *
	 * @param string $table
	 * @param mixed  $where  array('column' => $value, ...)
	 */
	public function delete($table, $where = null);

	/**
	 * Hilfsfunktion um eine Zeile zu bekommen
	 *
	 * @param  string $table
	 * @param  string $select
	 * @param  array  $where
	 * @param  int    $order
	 * @return array           row
	 */
	public function fetch($table, $select = '*', $where = null, $order = null);

	/**
	 * @return array
	 */
	public function all();

	/**
	 * @param  string $find
	 * @return array
	 */
	public function listTables($find = null);

	/**
	 * quotes a value (not recommended, better use prepared statements)
	 *
	 * @param  string $value
	 * @return string
	 */
	public function quote($value);

	/**
	 * quotes an column identifier
	 *
	 * @param  string $identifier
	 * @return string
	 */
	public function quoteIdentifier($identifier);
}
