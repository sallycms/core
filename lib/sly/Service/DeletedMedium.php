<?php
/*
 * Copyright (c) 2014, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\Util\Path;
use wv\BabelCache\CacheInterface;

/**
 * Service class for managing deleted media (aka files)
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_DeletedMedium extends sly_Service_Model_Base_Id implements sly_ContainerAwareInterface {
	protected $tablename = 'file'; ///< string
	protected $dispatcher;         ///< sly_Event_IDispatcher
	protected $container;          ///< sly_Container
	protected $cache;              ///< CacheInterface
	protected $mediaFs;            ///< sly_Filesystem_Interface

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param sly_Event_IDispatcher $dispatcher
	 * @param Filesystem            $mediaFs
	 */
	public function __construct(sly_DB_Persistence $persistence, CacheInterface $cache, sly_Event_IDispatcher $dispatcher, sly_Filesystem_Interface $mediaFs) {
		parent::__construct($persistence);

		$this->dispatcher = $dispatcher;
		$this->cache      = $cache;
		$this->mediaFs    = $mediaFs;
	}

	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	/**
	 * @return sly_Service_MediaCategory
	 */
	protected function getMediaCategoryService() {
		if (!$this->container) {
			throw new LogicException('Container must be set before media categories can be handled.');
		}

		return $this->container->getMediaCategoryService();
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Medium
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Medium($params);
	}

	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		if (is_string($where) && !empty($where)) {
			$where = "($where) AND deleted = 1";
		}
		else if (is_array($where)) {
			$where['deleted'] = 1;
		}
		else {
			$where = array('deleted' => 1);
		}

		return parent::find($where, $group, $order, $offset, $limit, $having);
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

		$medium = $this->findOne(array('id' => $id));

		return $medium;
	}

	/**
	 * @throws sly_Exception
	 * @param  int $mediumID
	 */
	public function deletePermanentById($mediumID) {
		$medium = $this->findById($mediumID);

		$this->deletePermanent($medium);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Medium $medium
	 */
	public function deletePermanent(sly_Model_Medium $medium) {
		if (!$medium) {
			throw new sly_Exception(t('medium_not_found'));
		}

		$sql     = $this->persistence;
		$mediaFs = $this->mediaFs;
		$table   = $this->tablename;

		try {
			$sql->transactional(function() use ($medium, $mediaFs, $sql, $table){
				$mediaFs->delete(basename($medium->getFilename()));
				$sql->delete($table, $medium->getPKHash());

			});
		}
		catch (Exception $e) {
			// re-wrap DB & PDO exceptions
			throw new sly_Exception($e->getMessage());
		}

		$this->dispatcher->notify('SLY_MEDIA_PERMANENT_DELETED', $medium);
	}

	/**
	 * @throws sly_Exception
	 * @param int $mediumID
	 */
	public function restoreMediumById($mediumID) {
		$medium = $this->findById($mediumID);

		$this->restoreMedium($medium);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Medium $medium
	 */
	public function restoreMedium(sly_Model_Medium $medium) {
		if (!$medium) {
			throw new sly_Exception(t('medium_not_found'));
		}

		try {
			if ($medium->getCategory() === null) {
				$medium->setCategoryId(0);
			}

			$medium->setDeleted(0);
			$this->save($medium);
			$this->cache->clear('sly.medium.list');
			$this->cache->delete('sly.medium', $medium->getId());
		}
		catch (Exception $e) {
			// re-wrap DB & PDO exceptions
			throw new sly_Exception($e->getMessage());
		}

		$this->dispatcher->notify('SLY_MEDIA_RESTORED', $medium);
	}
}
