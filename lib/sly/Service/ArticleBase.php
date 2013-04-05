<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleBase extends sly_Service_Model_Base implements sly_ContainerAwareInterface {
	const FIND_REVISION_LATEST = -2;
	const FIND_REVISION_ONLINE = -1;

	protected $tablename = 'article'; ///< string
	protected $container;             ///< sly_Container
	protected $languages = null;

	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	/**
	 * get article service
	 *
	 * @return sly_Service_Article
	 */
	protected function getArticleService() {
		return $this->container->getArticleService();
	}

	/**
	 * get category service
	 *
	 * @return sly_Service_Category
	 */
	protected function getCategoryService() {
		return $this->container->getCategoryService();
	}

	/**
	 * get cache instance
	 *
	 * @return BabelCache_Interface
	 */
	protected function getCache() {
		return $this->container->getCache();
	}

	/**
	 * get dispatcher instance
	 *
	 * @return sly_Event_IDispatcher
	 */
	public function getDispatcher() {
		return $this->container->getDispatcher();
	}

	/**
	 *
	 * @return sly_Service_Language
	 */
	public function getLanguages($keysOnly = true) {
		return $this->container->getLanguageService()->findAll($keysOnly);
	}

	abstract protected function getModelType();
	abstract protected function fixWhereClause($where);

	/**
	 * find latest revisions of articles
	 *
	 * @param  mixed    $where
	 * @param  string   $group
	 * @param  string   $order
	 * @param  int      $offset
	 * @param  int      $limit
	 * @param  string   $having
	 * @param  boolean  $findOnline
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $findOnline = false) {
		$return = array();
		$where  = $this->fixWhereClause($where);
		$db     = $this->getPersistence();
		$query  = $db->getSQLbuilder($db->getPrefix().$this->getTableName())->select('*');

		$query->where($where);
		if ($group)  $query->group($group);
		if ($having) $query->having($having);
		if ($offset) $query->offset($offset);
		if ($limit)  $query->limit($limit);
		if ($order) {
			$query->order($order.', revision DESC');
		}
		else {
			$query->order('revision DESC');
		}

		$outerQuery = 'SELECT * FROM ('.$query->to_s().') latest_'.$this->getTableName().'_tmp GROUP BY clang, id';

		$db->query($outerQuery, $query->bind_values());

		foreach ($db as $row) {
			$item = $this->makeInstance($row);
			if ($findOnline) {
				$item->setOnline(true);
			} else {
				$item->setOnline($this->getOnlineStatus($item));
			}
			$return[] = $item;
		}
		return $return;
	}

	/**
	 *
	 * @param mixed   $where
	 * @param boolean $findOnline
	 */
	public function findOne($where = null, $findOnline = false) {
		$items = $this->find($where, null, null, null, 1, null, $findOnline);
		return !empty($items) ? $items[0] : null;
	}

	/**
	 * finds article by id clang and revision
	 *
	 * @param  int $id
	 * @param  int $clang
	 * @param  int $revision          if null the latest revision will be fetched
	 * @return sly_Model_Base_Article
	 */
	protected function findByPK($id, $clang, $revision) {
		$id       = (int) $id;
		$clang    = (int) $clang;

		if ($id <= 0 || $clang <= 0) {
			return null;
		}

		$where    = compact('id', 'clang');

		if ($revision >= 0) {
			$where['revision'] = (int) $revision;
		}

		return $this->findOne($where, $revision === self::FIND_REVISION_ONLINE);
	}

	/**
	 *
	 * @param  int      $id
	 * @return boolean  Whether the article exists or not. Deleted equals existing and vise versa.
	 */
	public function exists($id) {
		$where = $this->fixWhereClause(compact('id'));
		$count = $this->getPersistence()->fetch($this->getTableName(), 'COUNT(id) as c', $where);
		return ((int) $count['c'] > 0);
	}

	/**
	 * @param  sly_Model_Base_Article $article
	 * @return sly_Model_Base_Article
	 */
	protected function update(sly_Model_Base_Article $obj) {
		$persistence = $this->getPersistence();
		$persistence->update($this->getTableName(), $obj->toHash(), $obj->getPKHash());

		return $obj;
	}

	/**
	 *
	 * @param  sly_Model_Base_Article $obj
	 * @return sly_Model_Base_Article
	 */
	protected function insert(sly_Model_Base_Article $obj) {
		$persistence = $this->getPersistence();
		$persistence->insert($this->getTableName(), array_merge($obj->toHash(), $obj->getPKHash()));
		return $obj;
	}

	protected function moveObjects($op, $where) {
		$db     = $this->getPersistence();
		$prefix = $db->getPrefix();
		$field  = $this->getModelType() === 'article' ? 'pos' : 'catpos';

		$db->query(sprintf(
			'UPDATE %sarticle SET %s = %s %s 1 WHERE %s',
			$prefix, $field, $field, $op, $where
		));
	}

	protected function buildPositionQuery($min, $max = null) {
		$field = $this->getModelType() === 'article' ? 'pos' : 'catpos';

		if ($max === null) {
			return sprintf('%s >= %d', $field, $min);
		}

		return sprintf('%s BETWEEN %d AND %d', $field, $min, $max);
	}

	protected function getFollowerQuery($parent, $clang, $min, $max = null) {
		$siblings = $this->getSiblingQuery($parent, $clang);
		$position = $this->buildPositionQuery($min, $max);

		return $siblings.' AND '.$position;
	}

	protected function getDefaultLanguageId() {
		return (int) sly_Core::getDefaultClangId();
	}

	protected function getEvent($name) {
		$type = $this->getModelType();
		return 'SLY_'.strtoupper(substr($type, 0, 3)).'_'.$name;
	}

	/**
	 * gets online status of article/category
	 * @param  array $items  sly_Model_Base_Article
	 * @return boolean
	 */
	protected function getOnlineStatus(sly_Model_Base_Article $item) {
		$sql   = $this->container->getPersistence();
		return $sql->magicFetch($this->getTableName(), 'MAX(revision)', array('id' => $item->getId(), 'clang' => $item->getClang())) == $item->getRevision();
	}
}
