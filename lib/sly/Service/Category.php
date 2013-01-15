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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Category extends sly_Service_ArticleBase {
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
		$where = array('re_id' => (int) $categoryID, 'startpage' => 1);

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
	 * get max category position
	 *
	 * @param  int $parentID
	 * @return int
	 */
	public function getMaxPosition($parentID) {
		$db     = $this->getPersistence();
		$where  = $this->getSiblingQuery($parentID);
		$maxPos = $db->magicFetch('article', 'MAX(catpos)', $where);

		return $maxPos;
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
	 * @return sly_Model_Category
	 */
	public function findById($id, $clang) {
		return parent::findById($id, $clang);
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
			$where['startpage'] = 1;
		}
		else {
			$where = "($where) AND startpage = 1";
		}

		return parent::find($where, $group, $order, $offset, $limit, $having);
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
	public function add($parentID, $name, $status = 0, $position = -1, sly_Model_User $user = null) {
		return $this->addHelper($parentID, $name, $status, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  int            $categoryID
	 * @param  int            $clangID
	 * @param  string         $name
	 * @param  mixed          $position
	 * @param  sly_Model_User $user        updateuser or null for the current user
	 * @return boolean
	 */
	public function edit($categoryID, $clangID, $name, $position = false, sly_Model_User $user = null) {
		return $this->editHelper($categoryID, $clangID, $name, $position, $user);
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

		$cat = $this->findById($categoryID);

		if ($cat === null) {
			throw new sly_Exception(t('category_not_found', $categoryID));
		}

		// allow external code to stop the delete operation
		$this->dispatcher->notify('SLY_PRE_CAT_DELETE', $cat);

		// check if this category still has children (both articles and categories)

		$children = $this->findByParentId($categoryID, false);

		if (!empty($children)) {
			throw new sly_Exception(t('category_is_not_empty'));
		}

		if (!($this->artService instanceof sly_Service_Article)) {
			throw new LogicException('You must set the article service with ->setArticleService() before you can delete categories.');
		}

		$children = $this->artService->findArticlesByCategory($categoryID, false);

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

			foreach ($this->lngService->findAll(true) as $clangID) {
				$catpos    = $this->findById($categoryID, $clangID)->getCatPosition();
				$followers = $this->getFollowerQuery($parent, $clangID, $catpos);

				$this->moveObjects('-', $followers);
			}

			// remove the start article of this category (and this also kills the category itself)
			$this->artService->deleteById($categoryID);

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
		$this->dispatcher->notify('SLY_CAT_DELETED', $cat);

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
	 * @param int            $categoryID  ID of the category that should be moved
	 * @param int            $targetID    target category ID
	 * @param sly_Model_User $user         updateuser or null for the current user
	 */
	public function move($categoryID, $targetID, sly_Model_User $user = null) {
		$categoryID  = (int) $categoryID;
		$targetID    = (int) $targetID;
		$defaultLang = $this->getDefaultLanguageId();
		$user        = $this->getActor($user, __METHOD__);
		$category    = $this->findById($categoryID, $defaultLang);
		$target      = $this->findById($targetID, $defaultLang);

		// check categories

		if ($category === null) {
			throw new sly_Exception(t('category_not_found', $categoryID));
		}

		if ($targetID !== 0 && $target === null) {
			throw new sly_Exception(t('target_category_not_found'));
		}

		if ($targetID !== 0 && $targetID === $categoryID) {
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
			$languages = $this->lngService->findAll(true);
			$newPos    = $this->getMaxPosition($targetID) + 1;
			$oldPath   = $category->getPath();
			$newPath   = $target ? ($target->getPath().$targetID.'|') : '|';

			// move the $category in each language by itself

			foreach ($languages as $clang) {
				$cat = $this->findById($categoryID, $clang);
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

			$from   = $oldPath.$categoryID.'|';
			$to     = $newPath.$categoryID.'|';
			$where  = 'path LIKE "'.$from.'%"';
			$update = 'path = REPLACE(path, "'.$from.'", "'.$to.'")';
			$prefix = sly_Core::getTablePrefix();

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
			$this->dispatcher->notify('SLY_CAT_MOVED', $categoryID, array(
				'clang'  => $clang,
				'target' => $targetID,
				'user'   => $user
			));
		}
	}

	/**
	 *
	 * @param  int      $id   The Category id
	 * @return boolean        Whether the article exists or not. Deleted equals not existing.
	 */
	public function exists($id) {
		$count = $this->getPersistence()->fetch($this->getTableName(), 'COUNT(id) as c', array('id' => $id, 'startpage' => 1, 'deleted' => 0));
		return ((int) $count['c'] > 0);
	}
}
