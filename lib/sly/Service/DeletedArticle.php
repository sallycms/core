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

	public function __construct(sly_DB_Persistence $persistence, BabelCache_Interface $cache) {
		parent::__construct($persistence);
		$this->cache = $cache;
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Article
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
	}

	public function findOne($where = null, $having = null) {
		$res = $this->find($where, null, 'revision DESC', null, 1, $having);
		return count($res) === 1 ? $res[0] : null;
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
		if (is_string($where) && !empty($where)) {
			$where = "($where) AND deleted = 1";
		}
		else if (is_array($where)) {
			$where['deleted'] = 1;
		}
		else {
			$where = array('deleted' => 1);
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

		$outerQuery = 'SELECT * FROM ('.$query->to_s().') latest_'.$this->getTableName().'_tmp GROUP BY id';

		$db->query($outerQuery, $query->bind_values());

		foreach ($db as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	/**
	 *
	 * @param  int            $id   a article id
	 * @param  sly_Model_User $user creator or null for the current user
	 * @throws sly_Exception
	 */
	public function restore($id, sly_Model_User $user = null) {
		$user    = $this->getActor($user, 'DeletedArticle restore');
		$article = $this->findOne(array('id' => $id, 'clang' => $this->getDefaultLanguageId()));

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		$categoryId = $article->getCategoryId();

		if ($categoryId !== 0 && !sly_Util_Category::exists($categoryId)) {
			throw new sly_Exception(t('category_not_found', $categoryId));
		}

		$newValues = array(
			'status'  => 0,
			'deleted' => 0
 		);


		$db = $this->getPersistence();
		$db->update($this->getTableName(), $newValues, array('id' => $id));
		$this->deleteListCache();
	}

	/**
	 *
	 * @param  int      $id
	 * @return boolean  Whether the article exists or not. Deleted equals existing and vise versa.
	 */
	public function exists($id) {
		$count = $this->getPersistence()->fetch($this->getTableName(), 'COUNT(id) as c', array('id' => $id, 'deleted' => 1));
		return ((int) $count['c'] > 0);
	}

	protected function getDefaultLanguageId() {
		return (int) sly_Core::getDefaultClangId();
	}

	public function deleteListCache() {
		$this->cache->flush('sly.article.list');
	}
}