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
 * @ingroup authorisation
 */
class sly_Authorisation_ArticleListProvider implements sly_Authorisation_ListProvider {

	private static $cache;

	private function initcache() {
		if(!isset(self::$cache)) {
			self::$cache = array();
			$query = sly_DB_Persistence::getInstance();
			$query->select('article', 'id, name', array('clang' => sly_Core::config()->get('DEFAULT_CLANG_ID')));
			foreach($query as $row) {
				self::$cache[$row['id']] = $row['name'];
			}
		}
	}

	public function getObjectIds() {
		$this->initcache();
		return array_keys(self::$cache);
	}

	public function getObjectTitle($id) {
		$this->initcache();
		return self::$cache[$id];
	}

}

?>
