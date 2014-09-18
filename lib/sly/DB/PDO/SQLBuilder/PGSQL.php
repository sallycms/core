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
 * @ingroup database
 */
class sly_DB_PDO_SQLBuilder_PGSQL extends sly_DB_PDO_SQLBuilder {
	/**
	 * @param  string $sql
	 * @param  int    $offset
	 * @param  int    $limit
	 * @return string
	 */
	public function build_limit($sql, $offset = 0, $limit = -1) {
		$limit = intval($limit);
		$limit = $limit > 0 && $limit !== null ? $limit : 'ALL';

		return $sql.' LIMIT '.$limit.' OFFSET '.intval($offset);
	}

	/**
	 * @return string
	 */
	public function build_list_tables() {
		// http://bytes.com/topic/postgresql/answers/172978-sql-command-list-tables
		return 'SELECT c.relname FROM pg_catalog.pg_class c '.
			'LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace '.
			'WHERE c.relkind IN (\'r\',\'\') AND n.nspname NOT IN (\'pg_catalog\', \'pg_toast\') '.
			'AND pg_catalog.pg_table_is_visible(c.oid)';
	}
}
