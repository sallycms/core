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
 * @author  christoph@webvariants.de, zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_Category extends sly_Service_ArticleManager {

	/**
	 * @return string
	 */
	protected function getModelType() {
		return 'category';
	}

	/**
	 * get WHERE statement for all category siblings
	 *
	 * @param  int     $categoryID
	 * @param  int     $clang       clang or null for none (*not* the current one)
	 * @param  boolean $asArray
	 * @return mixed                the condition either as an array or as a string
	 */
	protected function getSiblingQuery($categoryID, $clang = null, $asArray = false) {
		$where = array('re_id' => (int) $categoryID, 'startpage' => 1, 'deleted' => 0);

		if ($clang !== null) {
			$where['clang'] = (int) $clang;
		}

		if ($asArray) {
			return $where;
		}

		foreach ($where as $col => $value) {
			$where[$col] = "$col = $value";
		}

		return implode(' AND ', array_values($where));
	}

	/**
	 * build a new model from parameters
	 *
	 * @param  array  $params
	 * @return sly_Model_Article
	 */
	protected function buildModel(array $params) {
		return new sly_Model_Article(array(
			        'id' => $params['id'],
			     're_id' => $params['parent'],
			      'name' => $params['name'],
			   'catname' => $params['name'],
			    'catpos' => $params['position'],
			'attributes' => '',
			 'startpage' => 1,
			       'pos' => 1,
			      'path' => $params['path'],
			    'status' => $params['status'],
			      'type' => $params['type'],
			     'clang' => $params['clang'],
			   'deleted' => 0,
			  'revision' => 0
		));
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Category
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Category($params);
	}

	/**
	 * @param  int $id
	 * @param  int $clang
	 * @param  int $revision
	 * @return sly_Model_Category
	 */
	public function findByPK($id, $clang, $revision = null) {
		return parent::findByPK($id, $clang, $revision);
	}

	public function getPositionField() {
		return 'catpos';
	}

	/**
	 * @throws sly_Exception
	 * @param  int            $categoryID
	 * @param  string         $name
	 * @param  int            $status
	 * @param  int            $position
	 * @param  sly_Model_User $user      creator or null for the current user
	 * @return int
	 */
	public function add($categoryID, $name, $status = 0, $position = -1, sly_Model_User $user = null) {
		return $this->addHelper($categoryID, $name, $status, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Article_Base $obj
	 * @param  string                 $name
	 * @param  mixed                  $position
	 * @param  sly_Model_User         $user        updateuser or null for the current user
	 * @return boolean
	 */
	public function edit(sly_Model_Base_Article $obj, $name, $position = false, sly_Model_User $user = null) {
		return $this->editHelper($obj, $name, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Base_Article  $category
	 * @return boolean
	 */
	public function deleteByCategory(sly_Model_Base_Article $category) {
		return $this->deleteById($category->getId());
	}

	/**
	 * @throws sly_Exception
	 * @param  int $categoryID
	 * @return boolean
	 */
	public function deleteById($categoryID) {
		$categoryID = (int) $categoryID;
		$this->checkForSpecialArticle($categoryID);

		// does this category exist?

		$cat = $this->findByPK($categoryID, $this->getDefaultLanguageId());

		if ($cat === null) {
			throw new sly_Exception(t('category_not_found', $categoryID));
		}

		// allow external code to stop the delete operation
		$this->getDispatcher()->notify('SLY_PRE_CAT_DELETE', $cat);

		// check if this category still has children (both articles and categories)

		$children = $this->findByParentId($categoryID, false);

		if (!empty($children)) {
			throw new sly_Exception(t('category_is_not_empty'));
		}

		$children = $this->getArticleService()->findArticlesByCategory($categoryID, false);

		if (count($children) > 1 /* one child is expected, it's the category's start article */) {
			throw new sly_Exception(t('category_is_not_empty'));
		}

		// re-position all following categories
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$parent = $cat->getParentId();

			foreach ($this->getLanguages() as $clangID) {
				$catpos    = $this->findByPK($categoryID, $clangID)->getCatPosition();
				$followers = $this->getFollowerQuery($parent, $clangID, $catpos);

				$this->moveObjects('-', $followers);
			}

			// remove the start article of this category (and this also kills the category itself)
			$this->getArticleService()->deleteById($categoryID);

			if ($ownTrx) {
				$sql->commit();
			}
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$sql->rollBack();
			}

			throw $e;
		}

		// fire event
		$this->getDispatcher()->notify('SLY_CAT_DELETED', $cat);

		return true;
	}

	/**
	 * return all categories of a parent
	 *
	 * @param  int     $parentId
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public function findByParentId($parentId, $ignoreOfflines = false, $clang = null) {
		return $this->findElementsInCategory($parentId, $ignoreOfflines, $clang);
	}

	/**
	 * Selects a category and all children recursively
	 *
	 * @param  int $parentID   the sub-tree's root category or 0 for the whole tree
	 * @param  int $clang      the language or null for the current one
	 * @return array           sorted list of category IDs
	 */
	public function findTree($parentID, $clang = null) {
		$parentID = (int) $parentID;
		$clang    = $clang === null ? sly_Core::getCurrentClang() : (int) $clang;

		if ($parentID === 0) {
			return $this->find(array('clang' => $clang), null, 'id');
		}

		return $this->find('clang = '.$clang.' AND (id = '.$parentID.' OR path LIKE "%|'.$parentID.'|%")', null, 'id');
	}

	/**
	 * Moves a sub-tree to another category
	 *
	 * The sub-tree will be placed at the end of the target category.
	 *
	 * @param int            $id          ID of the category that should be moved
	 * @param int            $targetID    target category ID
	 * @param sly_Model_User $user         updateuser or null for the current user
	 */
	public function move($id, $targetID, sly_Model_User $user = null) {
		$id  = (int) $id;
		$targetID    = (int) $targetID;
		$defaultLang = $this->getDefaultLanguageId();
		$user        = $this->getActor($user, __METHOD__);
		$category    = $this->findByPK($id, $defaultLang);
		$target      = $this->findByPK($targetID, $defaultLang);

		// check categories

		if ($category === null) {
			throw new sly_Exception(t('category_not_found', $id));
		}

		if ($targetID !== 0 && $target === null) {
			throw new sly_Exception(t('target_category_not_found'));
		}

		if ($targetID !== 0 && $targetID === $id) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		// check self-include ($target may not be a child of $category)

		if ($target && $category->isAncestor($target)) {
			throw new sly_Exception(t('cannot_move_category_into_child'));
		}

		// prepare movement
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$oldParent = $category->getParentId();
			$languages = $this->getLanguages();
			$newPos    = $this->getMaxPosition($targetID) + 1;
			$oldPath   = $category->getPath();
			$newPath   = $target ? ($target->getPath().$targetID.'|') : '|';

			// move the $category in each language by itself

			foreach ($languages as $clang) {
				$cat = $this->findByPK($id, $clang);
				$pos = $cat->getCatPosition();

				$cat->setParentId($targetID);
				$cat->setCatPosition($newPos);
				$cat->setPath($newPath);
				$cat->setUpdateColumns($user);

				// update the cat itself
				$this->update($cat);

				// move all followers one position up
				$followers = $this->getFollowerQuery($oldParent, $clang, $pos);
				$this->moveObjects('-', $followers);
			}

			// update paths for all elements in the affected sub-tree

			$from   = $oldPath.$id.'|';
			$to     = $newPath.$id.'|';
			$where  = 'path LIKE "'.$from.'%"';
			$update = 'path = REPLACE(path, "'.$from.'", "'.$to.'")';
			$prefix = $sql->getPrefix();

			$sql->query('UPDATE '.$prefix.'article SET '.$update.' WHERE '.$where);
			$this->clearCacheByQuery($where);

			if ($ownTrx) {
				$sql->commit();
			}
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$sql->rollBack();
			}

			throw $e;
		}

		// notify system

		foreach ($languages as $clang) {
			$this->getDispatcher()->notify('SLY_CAT_MOVED', $id, array(
				'clang'  => $clang,
				'target' => $targetID,
				'user'   => $user
			));
		}
	}

	protected function fixWhereClause($where) {
		$where = parent::fixWhereClause($where);

		if (is_array($where)) {
			$where['startpage'] = 1;
		}
		else {
			$where = "($where) AND startpage = 1";
		}

		return $where;
	}
}
