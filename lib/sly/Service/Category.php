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

	protected function getMaxPosition($parentID) {
		$db     = sly_DB_Persistence::getInstance();
		$where  = $this->getSiblingQuery($parentID);
		$maxPos = $db->magicFetch('article', 'MAX(catpos)', $where);

		return $maxPos;
	}

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
	public function findById($id, $clang = null) {
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
	 * @param  int    $parentID
	 * @param  string $name
	 * @param  int    $status
	 * @param  int    $position
	 * @return int
	 */
	public function add($parentID, $name, $status = 0, $position = -1) {
		return $this->addHelper($parentID, $name, $status, $position);
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $categoryID
	 * @param  int    $clangID
	 * @param  string $name
	 * @param  mixed  $position
	 * @return boolean
	 */
	public function edit($categoryID, $clangID, $name, $position = false) {
		return $this->editHelper($categoryID, $clangID, $name, $position);
	}

	/**
	 * @throws sly_Exception
	 * @param  int $categoryID
	 * @return boolean
	 */
	public function delete($categoryID) {
		$categoryID = (int) $categoryID;
		$this->checkForSpecialArticle($categoryID);

		// does this category exist?

		$cat = $this->findById($categoryID);

		if ($cat === null) {
			throw new sly_Exception(t('category_doesnt_exist'));
		}

		// check if this category still has children (both articles and categories)

		$children = $this->findByParentId($categoryID, true);

		if ($this->findByParentId($categoryID, true)) {
			throw new sly_Exception('Category has still content and therefore cannot be deleted.');
		}

		// re-position all following categories

		$parent = $cat->getParentId();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$catpos    = $this->findById($categoryID, $clangID)->getCatPosition();
			$followers = $this->getFollowerQuery($parent, $clangID, $catpos);

			$this->moveObjects('-', $followers);
		}

		// remove the start article of this category (and this also kills the category itself)

		$service = sly_Service_Factory::getArticleService();
		$service->delete($categoryID);

		// fire event
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_DELETED', $cat);

		return true;
	}

	/**
	 * return all categories of a parent
	 *
	 * @param  int     $parentId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array
	 */
	public function findByParentId($parentId, $ignore_offlines = false, $clang = null) {
		return $this->findElementsInCategory($parentId, $ignore_offlines, $clang);
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
	 * @param int $categoryID  ID of the category that should be moved
	 * @param int $targetID    target category ID
	 */
	public function move($categoryID, $targetID) {
		$categoryID = (int) $categoryID;
		$targetID   = (int) $targetID;
		$category   = $this->findById($categoryID);
		$target     = $this->findById($targetID);

		// check categories

		if ($category === null) {
			throw new sly_Exception(t('no_such_category'));
		}

		if ($targetID !== 0 && $target === null) {
			throw new sly_Exception('The target category does not exist.');
		}

		if ($targetID !== 0 && $targetID === $categoryID) {
			throw new sly_Exception('Cannot move a category into itself.');
		}

		// check self-include ($target may not be a child of $category)

		if ($target && $category->isAncestor($target)) {
			throw new sly_Exception('Cannot move a category inside one of its children.');
		}

		// prepare movement

		$oldParent = $category->getParentId();
		$languages = sly_Util_Language::findAll(true);
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
		$sql    = sly_DB_Persistence::getInstance();
		$prefix = sly_Core::getTablePrefix();

		$sql->query('UPDATE '.$prefix.'article SET '.$update.' WHERE '.$where);
		$this->clearCacheByQuery($where);

		// notify system

		$dispatcher = sly_Core::dispatcher();

		foreach ($languages as $clang) {
			$dispatcher->notify('SLY_CAT_MOVED', $categoryID, array(
				'clang'  => $clang,
				'target' => $targetID
			));
		}
	}
}
