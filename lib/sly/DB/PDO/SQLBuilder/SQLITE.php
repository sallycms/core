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
class sly_DB_PDO_SQLBuilder_SQLITE extends sly_DB_PDO_SQLBuilder {
	/**
	 * @param  string $sql
	 * @param  int    $offset
	 * @param  int    $limit
	 * @return string
	 */
	public function build_limit($sql, $offset = 0, $limit = -1) {
		$offset = intval($offset);
		$limit  = intval($limit);

		return "$sql LIMIT $offset, $limit";
	}

	/**
	 * @return string
	 */
	public function build_list_tables() {
		// http://www.sqlite.org/faq.html#q7
		return 'SELECT name FROM sqlite_master WHERE type = \'table\' ORDER BY name';
	}
}
