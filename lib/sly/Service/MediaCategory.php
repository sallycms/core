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
 * DB Model Klasse für Medienkategorien
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_MediaCategory extends sly_Service_Model_Base_Id {
	protected $tablename = 'file_category'; ///< string

	const ERR_CAT_HAS_MEDIA   = 1; ///< int
	const ERR_CAT_HAS_SUBCATS = 2; ///< int

	/**
	 * @param  array $params
	 * @return sly_Model_MediaCategory
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_MediaCategory($params);
	}

	/**
	 * @param  int $id
	 * @return sly_Model_MediaCategory
	 */
	public function findById($id) {
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		$cat = sly_Core::cache()->get('sly.mediacat', $id, null);

		if ($cat === null) {
			$cat = $this->findOne(array('id' => $id));

			if ($cat !== null) {
				sly_Core::cache()->set('sly.mediacat', $id, $cat);
			}
		}

		return $cat;
	}

	/**
	 * @param  string $name
	 * @return array
	 */
	public function findByName($name) {
		return $this->findBy('byname_'.$name, array('name' => $name), 'id');
	}

	/**
	 * @param  int $id
	 * @return array
	 */
	public function findByParentId($id) {
		$id = (int) $id;

		if ($id < 0) {
			return array();
		}

		return $this->findBy($id, array('re_id' => $id), 'name');
	}

	/**
	 * @param  string $cacheKey
	 * @param  array  $where
	 * @param  string $sortBy
	 * @return array
	 */
	protected function findBy($cacheKey, $where, $sortBy) {
		$namespace = 'sly.mediacat.list';
		$list      = sly_Core::cache()->get($namespace, $cacheKey, null);

		if ($list === null) {
			$sql  = sly_DB_Persistence::getInstance();
			$list = array();

			$sql->select('file_category', 'id', $where, null, $sortBy);
			foreach ($sql as $row) $list[] = (int) $row['id'];

			sly_Core::cache()->set($namespace, $cacheKey, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	/**
	 * @throws sly_Exception
	 * @param  string                  $title
	 * @param  sly_Model_MediaCategory $parent
	 * @return sly_Model_MediaCategory
	 */
	public function add($title, sly_Model_MediaCategory $parent = null) {
		$title = trim($title);

		if (strlen($title) === 0) {
			throw new sly_Exception(t('mediacat_title_cannot_be_empty'));
		}

		$category = new sly_Model_MediaCategory();
		$category->setName($title);
		$category->setRevision(0);
		$category->setCreateColumns();

		$this->setPath($category, $parent);
		$this->save($category);

		// update cache
		sly_Core::cache()->flush('sly.mediacat.list');

		// notify system
		sly_Core::dispatcher()->notify('SLY_MEDIACAT_ADDED', $category);

		return $category;
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_MediaCategory $cat
	 */
	public function update(sly_Model_MediaCategory $cat) {
		if (strlen($cat->getName()) === 0) {
			throw new sly_Exception(t('mediacat_title_cannot_be_empty'));
		}

		$cat->setUpdateColumns();

		// ensure valid path & save it
		$this->setPath($cat, $cat->getParent());
		$this->save($cat);

		// update cache
		sly_Core::cache()->flush('sly.mediacat');

		// notify system
		sly_Core::dispatcher()->notify('SLY_MEDIACAT_UPDATED', $cat);
	}

	/**
	 * @throws sly_Exception
	 * @param  int     $catID
	 * @param  boolean $force
	 */
	public function delete($catID, $force = false) {
		$cat = $this->findById($catID);

		if (!$cat) {
			throw new sly_Exception('Cannot delete category: ID '.$catID.' not found.');
		}

		// check emptyness

		$children = $cat->getChildren();

		if (!$force && !empty($children)) {
			throw new sly_Exception('Cannot delete category: Category has sub-categories.', self::ERR_CAT_HAS_SUBCATS);
		}

		$media = $cat->getMedia();

		if (!$force && !empty($media)) {
			throw new sly_Exception('Cannot delete category: Category is not empty.', self::ERR_CAT_HAS_MEDIA);
		}

		// delete subcats

		foreach ($children as $child) {
			$this->delete($child, true);
		}

		// delete files

		$service = sly_Service_Factory::getMediumService();

		foreach ($media as $medium) {
			$service->delete($medium);
		}

		// delete cat itself
		$sql = sly_DB_Persistence::getInstance();
		$sql->delete('file_category', array('id' => $cat->getId()));

		// update cache
		sly_Core::cache()->flush('sly.mediacat');
		sly_Core::cache()->flush('sly.mediacat.list');

		// notify system
		sly_Core::dispatcher()->notify('SLY_MEDIACAT_DELETED', $cat);
	}

	/**
	 * @param sly_Model_MediaCategory $cat
	 * @param sly_Model_MediaCategory $parent
	 */
	protected function setPath(sly_Model_MediaCategory $cat, sly_Model_MediaCategory $parent = null) {
		if ($parent) {
			$parentID = $parent->getId();

			$cat->setParentId($parentID);
			$cat->setPath($parent->getPath().$parentID.'|');
		}
		else {
			$cat->setParentId(0);
			$cat->setPath('|');
		}
	}
}
