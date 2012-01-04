<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleBase extends sly_Service_Model_Base {
	protected $tablename = 'article'; ///< string
	protected $states    = array();   ///< array

	abstract protected function getModelType();
	abstract protected function getSiblingQuery($id, $clang = null);
	abstract protected function buildModel(array $params);

	abstract public function getMaxPosition($id);

	/**
	 * @param  sly_Model_Base_Article $article
	 * @return sly_Model_Base_Article
	 */
	protected function update(sly_Model_Base_Article $obj) {
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->update($this->getTableName(), $obj->toHash(), $obj->getPKHash());

		$this->deleteListCache();
		$this->deleteCache($obj->getId(), $obj->getClang());

		return $obj;
	}

	protected function getEvent($name) {
		$type = $this->getModelType();
		return 'SLY_'.strtoupper(substr($type, 0, 3)).'_'.$name;
	}

	protected function clearCacheByQuery($where) {
		$db = sly_DB_Persistence::getInstance();
		$db->select('article', 'id,clang', $where, 'id,clang');
		foreach ($db as $row) $this->deleteCache($row['id'], $row['clang']);
	}

	/**
	 * @param  int $id
	 * @param  int $clang
	 * @return sly_Model_Base_Article
	 */
	protected function findById($id, $clangID = null) {
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		if ($clangID === null || $clangID === false) {
			$clangID = sly_Core::getCurrentClang();
		}

		$type      = $this->getModelType();
		$namespace = 'sly.article';
		$key       = substr($type, 0, 3).'_'.$id.'_'.$clangID;
		$obj       = sly_Core::cache()->get($namespace, $key, null);

		if ($obj === null) {
			$obj = $this->findOne(array('id' => $id, 'clang' => $clangID));

			if ($obj !== null) {
				sly_Core::cache()->set($namespace, $key, $obj);
			}
		}

		return $obj;
	}

	/**
	 *
	 * @param  int $id
	 * @param  int $clangID
	 * @param  int $newStatus
	 * @return boolean
	 */
	public function changeStatus($id, $clangID, $newStatus = null) {
		$id      = (int) $id;
		$clangID = (int) $clangID;
		$obj     = $this->findById($id, $clangID);
		$type    = $this->getModelType();

		if (!$obj) {
			throw new sly_Exception(t($type.'_not_found'));
		}

		// if no explicit status is given, just take the next one

		if ($newStatus === null) {
			$states    = $this->getStates();
			$oldStatus = $obj->getStatus();
			$newStatus = ($oldStatus + 1) % count($states);
		}

		// update the article/category

		$obj->setStatus($newStatus);
		$obj->setUpdateColumns();
		$this->update($obj);

		// notify the system

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify($this->getEvent('STATUS'), $obj);

		return true;
	}

	/**
	 * @return array
	 */
	public function getStates() {
		$type = $this->getModelType();

		if (!isset($this->states[$type])) {
			$s = array(
				// display name, CSS class
				array(t('status_offline'), 'sly-offline'),
				array(t('status_online'),  'sly-online')
			);

			$s = sly_Core::dispatcher()->filter($this->getEvent('STATUS_TYPES'), $s);
			$this->states[$type] = $s;
		}

		return $this->states[$type];
	}

	/**
	 * @param int $id     article/category ID
	 * @param int $clang  language ID (give null to delete in all languages)
	 */
	public function deleteCache($id, $clang = null) {
		$cache = sly_Core::cache();

		foreach (sly_Util_Language::findAll(true) as $_clang) {
			if ($clang !== null && $clang != $_clang) {
				continue;
			}

			$cache->delete('sly.article', 'art_'.$id.'_'.$_clang);
			$cache->delete('sly.article', 'cat_'.$id.'_'.$_clang);
		}
	}

	public function deleteListCache() {
		$cache = sly_Core::cache();
		$cache->flush('sly.article.list');
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $parentID
	 * @param  string $name
	 * @param  int    $status
	 * @param  int    $position
	 * @return int
	 */
	protected function addHelper($parentID, $name, $status, $position = -1) {
		$parentID  = (int) $parentID;
		$position  = (int) $position;
		$status    = (int) $status;
		$modelType = $this->getModelType();
		$isArticle = $modelType === 'article';

		///////////////////////////////////////////////////////////////
		// check if parent exists

		if ($parentID !== 0 && !sly_Util_Category::exists($parentID)) {
			throw new sly_Exception(t('parent_category_not_found'));
		}

		///////////////////////////////////////////////////////////////
		// inherit type and catname from parent category

		$type          = sly_Core::getDefaultArticleType();
		$parentArticle = sly_Util_Article::findById($parentID);
		$db            = sly_DB_Persistence::getInstance();

		if ($parentID !== 0) {
			$type = $parentArticle->getType();
		}

		///////////////////////////////////////////////////////////////
		// validate target position

		$maxPos   = $this->getMaxPosition($parentID) + 1;
		$position = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

		///////////////////////////////////////////////////////////////
		// build tree path

		if ($parentID !== 0) {
			$path = $parentArticle->getPath().$parentID.'|';
		}
		else {
			$path = '|';
		}

		///////////////////////////////////////////////////////////////
		// move all following articles/categories down and remove them from cache

		if ($position < $maxPos) {
			$followers = $this->getFollowerQuery($parentID, null, $position);
			$this->moveObjects('+', $followers);
		}

		///////////////////////////////////////////////////////////////
		// create article/category rows for all languages

		$dispatcher = sly_Core::dispatcher();
		$newID      = $db->magicFetch('article', 'MAX(id)') + 1;

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$obj = $this->buildModel(array(
				      'id' => $newID,
				  'parent' => $parentID,
				    'name' => $name,
				'position' => $position,
				    'path' => $path,
				  'status' => $status ? 1 : 0,
				    'type' => $type,
				   'clang' => $clangID
			));

			$obj->setUpdateColumns();
			$obj->setCreateColumns();
			$db->insert($this->tablename, array_merge($obj->getPKHash(), $obj->toHash()));

			$this->deleteListCache();

			// notify system

			$dispatcher->notify($this->getEvent('ADDED'), $newID, array(
				're_id'    => $parentID,
				'clang'    => $clangID,
				'name'     => $name,
				'position' => $position,
				'path'     => $path,
				'status'   => $status,
				'type'     => $type
			));
		}

		return $newID;
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $id
	 * @param  int    $clangID
	 * @param  string $name
	 * @param  mixed  $position
	 * @return boolean
	 */
	protected function editHelper($id, $clangID, $name, $position = false) {
		$id        = (int) $id;
		$clangID   = (int) $clangID;
		$modelType = $this->getModelType();
		$isArticle = $modelType === 'article';

		///////////////////////////////////////////////////////////////
		// check object

		$obj = $this->findById($id, $clangID);

		if ($obj === null) {
			throw new sly_Exception(t($modelType.'_not_found'));
		}

		///////////////////////////////////////////////////////////////
		// update the object itself

		$isArticle ? $obj->setName($name) : $obj->setCatName($name);

		$obj->setUpdateColumns();
		$this->update($obj);

		///////////////////////////////////////////////////////////////
		// change catname of all children

		$db = sly_DB_Persistence::getInstance();

		if (!$isArticle) {
			$where = array('re_id' => $id, 'startpage' => 0, 'clang' => $clangID);
			$db->update('article', array('catname' => $name), $where);

			// and remove them from the cache
			$this->clearCacheByQuery($where);
		}

		///////////////////////////////////////////////////////////////
		// move object if required

		$curPos = $isArticle ? $obj->getPosition() : $obj->getCatPosition();

		if ($position !== false && $position != $curPos) {
			$position = (int) $position;
			$parentID = $isArticle ? $obj->getCategoryId() : $obj->getParentId();
			$maxPos   = $this->getMaxPosition($parentID);
			$newPos   = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

			// only do something if the position really changed

			if ($newPos != $curPos) {
				$relation    = $newPos < $curPos ? '+' : '-';
				list($a, $b) = $newPos < $curPos ? array($newPos, $curPos) : array($curPos, $newPos);

				// move all other objects

				$followers = $this->getFollowerQuery($parentID, $clangID, $a, $b);
				$this->moveObjects($relation, $followers);

				// save own, new position

				$isArticle ? $obj->setPosition($newPos) : $obj->setCatPosition($newPos);
				$this->update($obj);
			}
		}

		// be safe and clear all lists
		$this->deleteListCache();

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify($this->getEvent('UPDATED'), $obj);

		return true;
	}

	protected function moveObjects($op, $where) {
		$db     = sly_DB_Persistence::getInstance();
		$prefix = sly_Core::getTablePrefix();
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
		$list       = sly_Core::cache()->get($namespace, $key, null);

		if ($list === null) {
			$list  = array();
			$sql   = sly_DB_Persistence::getInstance();
			$where = $this->getSiblingQuery($categoryID, $clang);
			$pos   = $prefix === 'art' ? 'pos' : 'catpos';

			if ($ignoreOffline) {
				$where .= ' AND status = 1';
			}

			$sql->select($this->tablename, 'id', $where, null, $pos.',name');
			foreach ($sql as $row) $list[] = (int) $row['id'];

			sly_Core::cache()->set($namespace, $key, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$obj = $this->findById($id, $clang);
			if ($obj) $objlist[] = $obj;
		}

		return $objlist;
	}

	protected function checkForSpecialArticle($objID) {
		$objID = (int) $objID;

		if ($objID === sly_Core::getSiteStartArticleId()) {
			throw new sly_Exception(t('cannot_delete_start_article'));
		}

		if ($objID === sly_Core::getNotFoundArticleId()) {
			throw new sly_Exception(t('cannot_delete_not_found_article'));
		}
	}
}
