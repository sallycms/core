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
 * DB Model Klasse für Medien
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Medium extends sly_Service_Model_Base_Id {
	protected $tablename = 'file'; ///< string
	protected $cache;              ///< BabelCache_Interface
	protected $dispatcher;         ///< sly_Event_IDispatcher
	protected $catService;         ///< sly_Service_MediaCategory

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence        $persistence
	 * @param BabelCache_Interface      $cache
	 * @param sly_Event_IDispatcher     $dispatcher
	 * @param sly_Service_MediaCategory $catService
	 */
	public function __construct(sly_DB_Persistence $persistence, BabelCache_Interface $cache, sly_Event_IDispatcher $dispatcher, sly_Service_MediaCategory $catService) {
		parent::__construct($persistence);

		$this->cache      = $cache;
		$this->dispatcher = $dispatcher;
		$this->catService = $catService;
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Medium
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Medium($params);
	}

	/**
	 * @param  int $id
	 * @return sly_Model_Medium
	 */
	public function findById($id) {
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		$medium = $this->cache->get('sly.medium', $id, null);

		if ($medium === null) {
			$medium = $this->findOne(array('id' => $id));

			if ($medium !== null) {
				$this->cache->set('sly.medium', $id, $medium);
			}
		}

		return $medium;
	}

	/**
	 * @param  string $filename
	 * @return sly_Model_Medium
	 */
	public function findByFilename($filename) {
		$hash = md5($filename);
		$id   = $this->cache->get('sly.medium', $hash, null);

		if ($id === null) {
			$db = $this->getPersistence();
			$id = $db->magicFetch('file', 'id', array('filename' => $filename));

			if ($id === false) {
				return null;
			}

			$this->cache->set('sly.medium', $hash, $id);
		}

		return $this->findById($id);
	}

	/**
	 * @param  string $extension
	 * @return array
	 */
	public function findMediaByExtension($extension, $orderBy = 'filename', $direction = 'ASC') {
		$namespace = 'sly.medium.list';
		$list      = $this->cache->get($namespace, $extension, null);

		if ($list === null) {
			$sql  = $this->getPersistence();
			$list = array();

			$sql->select('file', 'id', array('SUBSTRING(filename, LOCATE(".", filename) + 1)' => $extension), null, $orderBy.' '.$direction);
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->cache->set($namespace, $extension, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	/**
	 * @param  int $categoryId
	 * @return array
	 */
	public function findMediaByCategory($categoryId, $orderBy = 'filename', $direction = 'ASC') {
		$categoryId = (int) $categoryId;
		$namespace  = 'sly.medium.list';
		$list       = $this->cache->get($namespace, $categoryId, null);

		if ($list === null) {
			$list  = array();
			$sql   = $this->getPersistence();
			$where = array('category_id' => $categoryId);

			$sql->select('file', 'id', $where, null, $orderBy.' '.$direction);
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->cache->set($namespace, $categoryId, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	/**
	 * @throws sly_Exception
	 * @param  string         $filename
	 * @param  string         $title
	 * @param  string         $title
	 * @param  int            $categoryID
	 * @param  string         $mimetype
	 * @param  string         $originalName
	 * @param  sly_Model_User $user          creator or null for the current user
	 * @return sly_Model_Medium
	 */
	public function add($filename, $title, $categoryID, $mimetype = null, $originalName = null, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		// check file itself

		$filename = basename($filename);
		$fullname = SLY_MEDIAFOLDER.'/'.$filename;

		if (!file_exists($fullname)) {
			throw new sly_Exception(t('file_not_found', $filename));
		}

		// check category

		$categoryID = (int) $categoryID;

		if ($this->catService->findById($categoryID) === null) {
			$categoryID = 0;
		}

		$size     = @getimagesize($fullname);
		$mimetype = empty($mimetype) ? sly_Util_Medium::getMimetype($fullname, $filename) : $mimetype;

		// create file object

		$file = new sly_Model_Medium();
		$file->setFiletype($mimetype);
		$file->setTitle($title);
		$file->setOriginalName($originalName === null ? $filename : basename($originalName));
		$file->setFilename($filename);
		$file->setFilesize(filesize($fullname));
		$file->setCategoryId($categoryID);
		$file->setRevision(0); // totally useless...
		$file->setReFileId(0); // even more useless
		$file->setAttributes('');
		$file->setCreateColumns($user);

		if ($size) {
			$file->setWidth($size[0]);
			$file->setHeight($size[1]);
		}
		else {
			$file->setWidth(0);
			$file->setHeight(0);
		}

		// store and return it

		$this->save($file);

		$this->cache->flush('sly.medium.list');
		$this->dispatcher->notify('SLY_MEDIA_ADDED', $file, compact('user'));

		return $file;
	}

	/**
	 * @param sly_Model_Medium $medium
	 * @param sly_Model_User   $user    user or null for the current user
	 */
	public function update(sly_Model_Medium $medium, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		// store data
		$medium->setUpdateColumns($user);
		$this->save($medium);

		// notify the listeners and clear our own cache
		$this->cache->delete('sly.medium', $medium->getId());
		$this->dispatcher->notify('SLY_MEDIA_UPDATED', $medium, compact('user'));
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Medium $medium
	 * @return boolean
	 */
	public function deleteByMedium(sly_Model_Medium $medium) {
		return $this->deleteById($medium->getId());
	}

	/**
	 * @throws sly_Exception
	 * @param  int $mediumID
	 * @return boolean
	 */
	public function deleteById($mediumID) {
		$medium = $this->findById($mediumID);

		if (!$medium) {
			throw new sly_Exception(t('medium_not_found', $mediumID));
		}

		try {
			$sql = $this->getPersistence();
			$sql->delete('file', array('id' => $medium->getId()));

			if ($medium->exists()) {
				unlink(SLY_MEDIAFOLDER.'/'.$medium->getFilename());
			}
		}
		catch (Exception $e) {
			// re-wrap DB & PDO exceptions
			throw new sly_Exception($e->getMessage());
		}

		$hash = md5($medium->getFilename());

		$this->cache->flush('sly.medium.list');
		$this->cache->delete('sly.medium', $medium->getId());
		$this->cache->delete('sly.medium', $hash);

		$this->dispatcher->notify('SLY_MEDIA_DELETED', $medium);

		return true;
	}
}
