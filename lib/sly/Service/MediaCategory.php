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
 * DB Model Klasse für Medienkategorien
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_MediaCategory extends sly_Service_Model_Base_Id {
	protected $tablename = 'file_category'; ///< string
	protected $cache;                       ///< BabelCache_Interface
	protected $dispatcher;                  ///< sly_Event_IDispatcher
	protected $mediumService;               ///< sly_Service_Medium

	const ERR_CAT_HAS_MEDIA   = 1; ///< int
	const ERR_CAT_HAS_SUBCATS = 2; ///< int

	/**
	 * Constructor
	 *
	 * Note that you have to call setMediumService() afterwards to have a
	 * fully-functional service.
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param BabelCache_Interface  $cache
	 * @param sly_Event_IDispatcher $dispatcher
	 */
	public function __construct(sly_DB_Persistence $persistence, BabelCache_Interface $cache, sly_Event_IDispatcher $dispatcher) {
		parent::__construct($persistence);

		$this->cache      = $cache;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Set medium service
	 *
	 * @param sly_Service_Medium $service
	 */
	public function setMediumService(sly_Service_Medium $service) {
		$this->mediumService = $service;
	}

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

		$cat = $this->cache->get('sly.mediacat', $id, null);

		if ($cat === null) {
			$cat = $this->findOne(array('id' => $id));

			if ($cat !== null) {
				$this->cache->set('sly.mediacat', $id, $cat);
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
	 * Selects a category and all children recursively
	 *
	 * @param  int     $parentID   the sub-tree's root category or 0 for the whole tree
	 * @param  boolean $asObjects  set to false if you need the IDs only
	 * @return array               sorted list of category IDs
	 */
	public function findTree($parentID, $asObjects = true) {
		$parentID = (int) $parentID;

		if ($parentID === 0) {
			return $this->findBy('tree_0', '1', 'id', $asObjects);
		}

		return $this->findBy('tree_'.$parentID, 'id = '.$parentID.' OR path LIKE "%|'.$parentID.'|%"', 'id', $asObjects);
	}

	/**
	 * @param  string  $cacheKey
	 * @param  array   $where
	 * @param  string  $sortBy
	 * @param  boolean $asObjects  set to false if you need the IDs only
	 * @return array
	 */
	protected function findBy($cacheKey, $where, $sortBy, $asObjects = true) {
		$namespace = 'sly.mediacat.list';
		$list      = $this->cache->get($namespace, $cacheKey, null);

		if ($list === null) {
			$sql  = $this->getPersistence();
			$list = array();

			$sql->select('file_category', 'id', $where, null, $sortBy);
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->cache->set($namespace, $cacheKey, $list);
		}

		if (!$asObjects) {
			return $list;
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
	 * @param  sly_Model_User          $user    creator or null for the current user
	 * @return sly_Model_MediaCategory
	 */
	public function add($title, sly_Model_MediaCategory $parent = null, sly_Model_User $user = null) {
		$title = trim($title);
		$user  = $this->getActor($user, __METHOD__);

		if (mb_strlen($title) === 0) {
			throw new sly_Exception(t('plase_enter_a_name'));
		}

		$category = new sly_Model_MediaCategory();
		$category->setName($title);
		$category->setRevision(0);
		$category->setAttributes('');
		$category->setCreateColumns($user);

		$this->setPath($category, $parent);
		$this->save($category);

		// update cache
		$this->cache->flush('sly.mediacat.list');

		// notify system
		$this->dispatcher->notify('SLY_MEDIACAT_ADDED', $category, compact('user'));

		return $category;
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_MediaCategory $cat
	 * @param  sly_Model_User          $user  updateuser or null for the current user
	 */
	public function update(sly_Model_MediaCategory $cat, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		if (mb_strlen($cat->getName()) === 0) {
			throw new sly_Exception(t('title_cannot_be_empty'));
		}

		$cat->setUpdateColumns($user);

		// ensure valid path & save it
		$this->setPath($cat, $cat->getParent());
		$this->save($cat);

		// update cache
		$this->cache->flush('sly.mediacat');

		// notify system
		$this->dispatcher->notify('SLY_MEDIACAT_UPDATED', $cat, compact('user'));
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_MediaCategory $cat
	 * @param  boolean                 $force
	 */
	public function deleteByCategory(sly_Model_MediaCategory $cat, $force = false) {
		$this->deleteById($cat->getId(), $force);
	}

	/**
	 * @throws sly_Exception
	 * @param  int     $catID
	 * @param  boolean $force
	 */
	public function deleteById($catID, $force = false) {
		$cat = $this->findById($catID);

		if (!$cat) {
			throw new sly_Exception(t('category_not_found', $catID));
		}

		// check emptyness

		$children = $cat->getChildren();

		if (!$force && !empty($children)) {
			throw new sly_Exception(t('category_has_children'), self::ERR_CAT_HAS_SUBCATS);
		}

		$media = $cat->getMedia();

		if (!$force && !empty($media)) {
			throw new sly_Exception(t('category_is_not_empty'), self::ERR_CAT_HAS_MEDIA);
		}

		$service = $this->mediumService;

		if (!($service instanceof sly_Service_Medium)) {
			throw new LogicException('You must set the medium service with ->setMediumService() before you can delete categories.');
		}

		$db     = $this->getPersistence();
		$ownTrx = !$db->isTransRunning();

		if ($ownTrx) {
			$db->beginTransaction();
		}

		try {
			// delete subcats
			foreach ($children as $child) {
				$this->deleteById($child->getId(), true);
			}

			// delete files
			foreach ($media as $medium) {
				$service->deleteByMedium($medium);
			}

			// delete cat itself
			$db->delete('file_category', array('id' => $cat->getId()));

			if ($ownTrx) {
				$db->commit();
			}
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$db->rollBack();
			}

			throw $e;
		}

		// update cache
		$this->cache->flush('sly.mediacat');
		$this->cache->flush('sly.mediacat.list');

		// notify system
		$this->dispatcher->notify('SLY_MEDIACAT_DELETED', $cat);
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
