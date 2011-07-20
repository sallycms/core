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
	protected $tablename = 'file_category';

	const ERR_CAT_HAS_MEDIA   = 1;
	const ERR_CAT_HAS_SUBCATS = 2;

	protected function makeInstance(array $params) {
		return new sly_Model_MediaCategory($params);
	}

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

	public function findByName($name) {
		return $this->findBy('byname_'.$name, array('name' => $name), 'id');
	}

	public function findByParentId($id) {
		$id = (int) $id;

		if ($id <= 0) {
			return array();
		}

		return $this->findBy($id, array('re_id' => $id), 'name');
	}

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

	public function add($title, sly_Model_MediaCategory $parent = null) {
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

	public function update(sly_Model_MediaCategory $cat) {
		$category->setUpdateColumns();

		// ensure valid path & save it
		$this->setPath($cat, $cat->getParent());
		$this->save($category);

		// update cache
		sly_Core::cache()->flush('sly.mediacat.list');

		// notify system
		sly_Core::dispatcher()->notify('SLY_MEDIACAT_UPDATED', $category);
	}

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

		// notify system
		sly_Core::dispatcher()->notify('SLY_MEDIACAT_DELETED', $cat);
	}

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
