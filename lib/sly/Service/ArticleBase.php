<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleBase extends sly_Service_Model_Base implements sly_ContainerAwareInterface {
	const FIND_REVISION_ONLINE = -1;
	const FIND_REVISION_LATEST = -2;
	const FIND_REVISION_BEST   = -3;

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
	 * @return wv\BabelCache\CacheInterface
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
	 * @param  int      $revStrategy  FIND_REVISION_ONLINE, FIND_REVISION_LATEST or null to disable any kind of revision filtering
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $revStrategy = self::FIND_REVISION_LATEST) {
		$db        = $this->getPersistence();
		$where     = $this->fixWhereClause($where);
		$return    = array();

		// SELECT * FROM sly_article
		// WHERE ($where) AND (latest = 1[ OR online = 1])
		// [GROUP BY $group] [HAVING $having]
		// ORDER BY online DESC[, $order]
		// [LIMIT [$offset,]$limit]

		// transform $where into a string, so we can add our rev strategy clauses
		if (sly_Util_Array::isAssoc($where)) {
			foreach($where as $key => $value) {
				$where[$key] = $db->quoteIdentifier($key).' = '.$db->quote($value);
			}

			$where = join(' AND ', $where);
		}

		$strat = null;

		switch ($revStrategy) {
			case self::FIND_REVISION_ONLINE:
				$strat = $db->quoteIdentifier('online').' = '.$db->quote(1);
				break;
			case self::FIND_REVISION_LATEST:
				$strat = $db->quoteIdentifier('latest').' = '.$db->quote(1);
				break;
			case self::FIND_REVISION_BEST:
				$strat = $db->quoteIdentifier('latest').' = '.$db->quote(1).' OR '.$db->quoteIdentifier('online').' = '.$db->quote(1);
				//$order = $order ? "$order, online DESC, latest DESC" : 'online DESC, latest DESC';
				break;
			default:
				$strat = null;
				break;
		}

		if ($where === null) {
			$where = $strat;
		}
		elseif ($strat) {
			$where = "($where) AND ($strat)";
		}

		// add some more order information
		$db->select($this->tablename, '*', $where, $group, $order, $offset, $limit, $having);

		$fetched = array();

		foreach ($db as $row) {
			// based on the revision strategy, we only consider a subset of found revisions;
			// perform the filtering before constructing the actual model instance
			
			$key = $row['id'].'_'.$row['clang'];
			
			if ($revStrategy !== self::FIND_REVISION_BEST || !isset($fetched[$key]) || $row['online']) {
				$fetched[$key] = $row;
			}
		}

		foreach ($fetched as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	/**
	 * finds articles and return the first one
	 *
	 * @param mixed $where
	 * @param int   $revStrategy  FIND_REVISION_ONLINE, FIND_REVISION_LATEST or null to disable any kind of revision filtering
	 */
	public function findOne($where = null, $revStrategy = self::FIND_REVISION_LATEST) {
		$items = $this->find($where, null, null, null, null, null, $revStrategy);
		return !empty($items) ? $items[0] : null;
	}

	public function count($where = null, $group = null) {
		$where = $this->fixWhereClause($where);
		return parent::count($where, $group);
	}

	/**
	 * finds article by id, clang and revision
	 *
	 * @param  int $id
	 * @param  int $clang
	 * @param  int $revision           a specific revision or one of the FIND_* constants
	 * @return sly_Model_Base_Article
	 */
	protected function findByPK($id, $clang, $revision) {
		$id    = (int) $id;
		$clang = (int) $clang;

		if ($id <= 0 || $clang <= 0) {
			return null;
		}

		$where = compact('id', 'clang');

		if ($revision >= 0) {
			$where['revision'] = (int) $revision;
		}

		return $this->findOne($where, $revision >= 0 ? null : $revision);
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
			'UPDATE %s SET %s = %s %s 1 WHERE %s',
			$db->getConnection()->getTable($this->tablename),
			$db->quoteIdentifier($field),
			$db->quoteIdentifier($field),
			$op,
			$where
		));
	}

	protected function buildPositionQuery($min, $max = null) {
		$field = $this->getModelType() === 'article' ? 'pos' : 'catpos';

		if ($max === null) {
			return sprintf('%s >= %d', $field, $min);
		}

		return sprintf('%s BETWEEN %d AND %d', $this->getPersistence()->quoteIdentifier($field), $min, $max);
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
}
