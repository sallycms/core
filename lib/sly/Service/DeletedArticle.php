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
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_DeletedArticle extends sly_Service_Model_Base {
	protected $tablename = 'article'; ///< string

	public function __construct(sly_DB_Persistence $persistence) {
		parent::__construct($persistence);
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Article
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
	}

	/**
	 * @param  mixed  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		if (is_array($where)) {
			$where['deleted'] = 1;
		}
		else {
			$where = "($where) AND deleted = 1";

		}

		return parent::find($where, $group, $order, $offset, $limit, $having);
	}

	/**
	 * find latest revisions of deleted articles
	 *
	 * @param  mixed  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @return array
	 */
	public function findLatest($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$return = array();
		$db     = $this->getPersistence();
		$query  = $db->getSQLbuilder($db->getPrefix().$this->getTableName())->select('*');

		if ($where)  $query->where($where);
		if ($group)  $query->group($group);
		if ($having) $query->having($having);
		if ($offset) $query->offset($offset);
		if ($limit)  $query->limit($limit);
		if ($order) {
			$query->order('revision,'.$order);
		}
		else {
			$query->order('revision');
		}

		$outerQuery = 'SELECT * FROM ('.$query->to_s().') latest_article_tmp GROUP BY id';

		$db->query($outerQuery, $query->bind_values());

		foreach ($db as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

}