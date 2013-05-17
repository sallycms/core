<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleManager extends sly_Service_ArticleBase {
	protected $states = array();   ///< array

	abstract protected function buildModel(array $params);
	abstract protected function getSiblingQuery($id, $clang = null);

	abstract public function move($id, $target, sly_Model_User $user = null);
	abstract public function add($categoryID, $name, $position = -1, sly_Model_User $user = null);
	abstract public function edit(sly_Model_Base_Article $obj, $name, $position = false, sly_Model_User $user = null);
	abstract public function getPositionField();

	/**
	 * get maximum position eigther catpos or pos whether it is a article or
	 * category service
	 *
	 * @param  int $parentID
	 * @return int
	 */
	public function getMaxPosition($parentID) {
		$db     = $this->getPersistence();
		$where  = $this->getSiblingQuery($parentID);
		$field  = $this->getPositionField();
		$maxPos = $db->magicFetch($this->getTableName(), 'MAX('.$field.')', $where);

		return $maxPos;
	}

	/**
	 * @throws sly_Exception
	 * @param  int            $parentID
	 * @param  string         $name
	 * @param  int            $status
	 * @param  int            $position
	 * @param  sly_Model_User $user      creator or null for the current user
	 * @return int
	 */
	protected function addHelper($parentID, $name, $position = -1, sly_Model_User $user = null) {
		$parentID    = (int) $parentID;
		$position    = (int) $position;
		$name        = (string) $name;
		$defaultLang = $this->getDefaultLanguageId();
		$user        = $this->getActor($user, 'add');

		// get the parent
		$parentArticle = $this->getArticleService()->findByPK($parentID, $defaultLang, self::FIND_REVISION_LATEST);

		///////////////////////////////////////////////////////////////
		// check if parent exists

		if ($parentID !== 0 && $parentArticle === null) {
			throw new sly_Exception(t('parent_category_not_found'));
		}

		///////////////////////////////////////////////////////////////
		// inherit type and catname from parent category

		$type = sly_Core::getDefaultArticleType();
		$db   = $this->getPersistence();

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
		// move all following articles/categories down

		if ($position < $maxPos) {
			$followers = $this->getFollowerQuery($parentID, null, $position);
			$this->moveObjects('+', $followers);
		}

		///////////////////////////////////////////////////////////////
		// create article/category rows for all languages

		$dispatcher = $this->getDispatcher();
		$languages  = $this->getLanguages();

		$trx = $db->beginTrx();

		try {
			$newID = $db->magicFetch('article', 'MAX(id)') + 1;
			$table = $this->getTableName();
			$event = $this->getEvent('ADDED');

			foreach ($languages as $clangID) {
				$obj = $this->buildModel(array(
					      'id' => $newID,
					  'parent' => $parentID,
					    'name' => $name,
					'position' => $position,
					    'path' => $path,
					    'type' => $type,
					   'clang' => $clangID,
					'revision' => 0
				));

				$obj->setUpdateColumns($user);
				$obj->setCreateColumns($user);
				$db->insert($table, array_merge($obj->getPKHash(), $obj->toHash()));

				// notify system

				$dispatcher->notify($event, $obj, array(
					're_id'    => $parentID,
					'clang'    => $clangID,
					'name'     => $name,
					'position' => $position,
					'path'     => $path,
					'type'     => $type,
					'user'     => $user,
					'revision' => 0
				));
			}

			$db->commitTrx($trx);
		}
		catch (Exception $e) {
			$db->rollBackTrx($trx, $e);
		}

		return $newID;
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Article_Base $obj
	 * @param  string                 $name
	 * @param  mixed                  $position
	 * @param  sly_Model_User         $user      creator or null for the current user
	 * @return boolean
	 */
	protected function editHelper(sly_Model_Base_Article $obj, $name, $position = false, sly_Model_User $user = null) {
		$modelType = $this->getModelType();
		$isArticle = $modelType === 'article';
		$user      = $this->getActor($user, 'edit');

		///////////////////////////////////////////////////////////////
		// check object

		if ($obj === null) {
			throw new sly_Exception(t($modelType.'_not_found', $ids));
		}

		$id         = $obj->getId();
		$clangID    = $obj->getClang();
		$db         = $this->getPersistence();
		$dispatcher = $this->getDispatcher();

		$trx = $db->beginTrx();

		try {
			// update the object itself

			$isArticle ? $obj->setName($name) : $obj->setCatName($name);

			$obj->setUpdateColumns($user);

			$obj = $this->update($obj);

			// change catname of all children

			if (!$isArticle) {
				$where = array('re_id' => $id, 'startpage' => 0, 'clang' => $clangID);
				$db->update('article', array('catname' => $name), $where);
			}

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

					// save own, new position for all revisions

					$field = $isArticle ? 'pos' : 'catpos';
					$where = array('id' => $obj->getId(), 'clang' => $obj->getClang());

					$db->update($this->getTableName(), array($field => $newPos), $where);
				}
			}

			$dispatcher->notify($this->getEvent('UPDATED'), $obj, array('user' => $user));

			$db->commitTrx($trx);
		}
		catch (Exception $e) {
			$db->rollBackTrx($trx, $e);
		}

		return true;
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

	protected function fixWhereClause($where) {
		if (is_string($where) && !empty($where)) {
			$where = "($where) AND deleted = 0";
		}
		else if (is_array($where)) {
			$where['deleted'] = 0;
		}
		else {
			$where = array('deleted' => 0);
		}

		return $where;
	}

	/**
	 * @param  int     $categoryID
	 * @param  int     $clang
	 * @param  boolean $findOnline
	 * @return array
	 */
	protected function findElementsInCategory($categoryID, $clang, $findOnline = false) {
		$categoryID = (int) $categoryID;
		$clang      = (int) $clang;
		$where      = $this->getSiblingQuery($categoryID, $clang);
		$order      = $this->getPositionField();

		return $this->find($where, null, $order, null, null, null, $findOnline);
	}
}
