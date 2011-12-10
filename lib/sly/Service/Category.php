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

	protected function getMaxPrior($parentID) {
		$db     = sly_DB_Persistence::getInstance();
		$where  = $this->getSiblingQuery($parentID);
		$maxPos = $db->magicFetch('article', 'MAX(catprior)', $where);

		return $maxPos;
	}

	protected function buildModel(array $params) {
		return new sly_Model_Article(array(
			        'id' => $params['id'],
			     're_id' => $params['parent'],
			      'name' => $params['name'],
			   'catname' => $params['name'],
			  'catprior' => $params['position'],
			'attributes' => '',
			 'startpage' => 1,
			     'prior' => 1,
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
			$catprior  = $this->findById($categoryID, $clangID)->getCatprior();
			$followers = $this->getFollowerQuery($parent, $clangID, $catprior);

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
	 * @return array           sorted list of category IDs
	 */
	public function findTree($parentID) {
		$parentID = (int) $parentID;

		if ($parentID === 0) {
			return $this->find(array(), null, 'id', $asObjects);
		}

		return $this->find('id = '.$parentID.' OR path LIKE "%|'.$parentID.'|%"', null, 'id');
	}
}
