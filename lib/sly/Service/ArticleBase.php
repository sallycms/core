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
		$where  = $this->fixWhereClause($where);
		$db     = $this->getPersistence();
		$query  = $db->getSQLbuilder($db->getPrefix().$this->getTableName())->select('*');

		$query->where($where);
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

		$outerQuery = 'SELECT * FROM ('.$query->to_s().') latest_'.$this->getTableName().'_tmp GROUP BY clang, id';

		$db->query($outerQuery, $query->bind_values());

		foreach ($db as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
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
		$where = $this->fixWhereClause($where);

		return parent::find($where, $group, $order, $offset, $limit, $having);
	}

	/**
	 * @param  array  $where
	 * @param  string $having
	 * @return sly_Model_Base
	 */
	public function findOne($where = null, $having = null) {
		$res = $this->find($where, null, 'revision DESC', null, 1, $having);
		return count($res) === 1 ? $res[0] : null;
	}

	/**
	 * @param  sly_Model_Base_Article $article
	 * @return sly_Model_Base_Article
	 */
	protected function update(sly_Model_Base_Article $obj) {
		$persistence = $this->getPersistence();
		$persistence->update($this->getTableName(), $obj->toHash(), $obj->getPKHash());

		$this->deleteListCache();
		$this->deleteCache($obj->getId(), $obj->getClang());

		return $obj;
	}

	/**
	 *
	 * @param sly_Model_Base_Article $obj
	 * @return sly_Model_Base_Article
	 */
	protected function insert(sly_Model_Base_Article $obj) {
		$persistence = $this->getPersistence();
		$persistence->insert($this->getTableName(), array_merge($obj->toHash(), $obj->getPKHash()));
		return $obj;
	}

	/**
	 * finds article by id clang and revision
	 *
	 * @param  int $id
	 * @param  int $clang
	 * @param  int $revision          if null the latest revision will be fetched
	 * @return sly_Model_Base_Article
	 */
	protected function findById($id, $clang, $revision = null) {
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		$where     = compact('id', 'clang');
		$useCache  = $revision === null;
		$type      = $this->getModelType();
		$namespace = 'sly.article';
		$key       = substr($type, 0, 3).'_'.$id.'_'.$clang;
		$obj       = $useCache ? $this->getCache()->get($namespace, $key, null) : null;

		if ($obj === null) {

			if ($revision !== null) {
				$where['revision'] = $revision;
			}

			$obj = $this->findOne($where);

			if ($useCache && $obj !== null) {
				$this->getCache()->set($namespace, $key, $obj);
			}
		}

		return $obj;
	}

	/**
	 * @param int $id     article/category ID
	 * @param int $clang  language ID (give null to delete in all languages)
	 */
	protected function deleteCache($id, $clang = null) {
		foreach ($this->getLanguages() as $_clang) {
			if ($clang !== null && $clang != $_clang) {
				continue;
			}

			$this->getCache()->delete('sly.article', 'art_'.$id.'_'.$_clang);
			$this->getCache()->delete('sly.article', 'cat_'.$id.'_'.$_clang);
		}
	}

	/**
	 *
	 * @param  int      $id
	 * @return boolean  Whether the article/category exists or not. Deleted equals not existing.
	 */
	public function exists($id) {
		return $this->findById($id, $this->getDefaultLanguageId()) !== null;
	}

	protected function moveObjects($op, $where) {
		$db     = $this->getPersistence();
		$prefix = $db->getPrefix();
		$field  = $this->getModelType() === 'article' ? 'pos' : 'catpos';

		$this->clearCacheByQuery($where);

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

	/**
	 * @param  int     $categoryID
	 * @param  boolean $ignoreOffline
	 * @param  int     $clang
	 * @return array
	 */
	protected function findElementsInCategory($categoryID, $ignoreOffline = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		$categoryID = (int) $categoryID;
		$clang      = (int) $clang;
		$namespace  = 'sly.article.list';
		$prefix     = substr($this->getModelType(), 0, 3);
		$key        = $prefix.'sbycat_'.$categoryID.'_'.$clang.'_'.($ignoreOffline ? '1' : '0');
		$list       = $this->getCache()->get($namespace, $key, null);

		if ($list === null) {
			$list  = array();
			$sql   = $this->getPersistence();
			$where = $this->getSiblingQuery($categoryID, $clang);
			$pos   = $prefix === 'art' ? 'pos' : 'catpos';

			if ($ignoreOffline) {
				$where .= ' AND status = 1';
			}

			$sql->select($this->tablename, 'id', $where, 'id', $pos.',name');
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->getCache()->set($namespace, $key, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$obj = $this->findById($id, $clang);
			if ($obj) $objlist[] = $obj;
		}

		return $objlist;
	}

	protected function getDefaultLanguageId() {
		return (int) sly_Core::getDefaultClangId();
	}

	protected function getEvent($name) {
		$type = $this->getModelType();
		return 'SLY_'.strtoupper(substr($type, 0, 3)).'_'.$name;
	}

	protected function clearCacheByQuery($where) {
		$db = $this->getPersistence();
		$db->select('article', 'id,clang', $where, 'id,clang');

		foreach ($db as $row) {
			$this->deleteCache($row['id'], $row['clang']);
		}
	}

	protected function deleteListCache() {
		$this->getCache()->flush('sly.article.list');
	}
}
