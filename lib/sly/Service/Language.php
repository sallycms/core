<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\CacheInterface;

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Language extends sly_Service_Model_Base_Id {
	protected $tablename = 'clang'; ///< string
	protected $cache;               ///< CacheInterface
	protected $dispatcher;          ///< sly_Event_IDispatcher

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param CacheInterface        $cache
	 * @param sly_Event_IDispatcher $dispatcher
	 */
	public function __construct(sly_DB_Persistence $persistence, CacheInterface $cache, sly_Event_IDispatcher $dispatcher) {
		parent::__construct($persistence);

		$this->cache      = $cache;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Language
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Language($params);
	}

	/**
	 * @param  int $articleId
	 * @param  int $clang
	 * @return sly_Model_Language
	 */
	public function findById($languageID) {
		$languages  = $this->findAll();
		$languageID = (int) $languageID;

		return isset($languages[$languageID]) ? $languages[$languageID] : null;
	}

	/**
	 * @param  boolean $keysOnly
	 * @return array
	 */
	public function findAll($keysOnly = false) {
		$languages = $this->cache->get('sly.language', 'all', null);

		if ($languages === null) {
			$list      = $this->find(null, null, 'id');
			$languages = array();

			foreach ($list as $language) {
				$languages[$language->getId()] = $language;
			}

			$this->cache->set('sly.language', 'all', $languages);
		}

		return $keysOnly ? array_keys($languages) : $languages;
	}

	/**
	 * @param  sly_Model_Base $model
	 * @return sly_Model_Base
	 */
	public function save(sly_Model_Base $model) {
		$this->cache->delete('sly.language', 'all');

		$result = parent::save($model);

		// notify listeners
		$this->dispatcher->notify('SLY_CLANG_UPDATED', $model);

		return $result;
	}

	/**
	 * @throws Exception           if something goes wrong
	 * @param  array $params
	 * @return sly_Model_Language
	 */
	public function create($params) {
		$langs = $this->findAll();

		// if there are no languages yet, don't attempt to copy anything

		if (count($langs) === 0) {
			$newLanguage = parent::create($params);
		}
		else {
			$sql = $this->getPersistence();
			$trx = $sql->beginTrx();

			try {
				$newLanguage = parent::create($params);
				$sourceID    = sly_Core::getDefaultClangId();

				// if a bad default language was configured, use the first one
				if (!isset($langs[$sourceID])) {
					$ids = array_keys($langs);
					sort($ids);
					$sourceID = reset($ids);
				}

				$sql->query(str_replace('~', $sql->getPrefix(),
					'INSERT INTO ~article (id,re_id,name,catname,catpos,attributes,'.
					'startpage,pos,path,createdate,updatedate,type,clang,createuser,'.
					'updateuser,revision) '.
					'SELECT id,re_id,name,catname,catpos,attributes,startpage,pos,path,createdate,'.
					'updatedate,type,?,createuser,updateuser,revision '.
					'FROM ~article WHERE clang = ?'),
					array($newLanguage->getId(), $sourceID)
				);

				$sql->commitTrx($trx);
			}
			catch (Exception $e) {
				$sql->rollBackTrx($trx, $e);
			}
		}

		// update cache before notifying the listeners (so that they can call findAll() and get fresh data)
		$langs[$newLanguage->getId()] = $newLanguage;
		$this->cache->set('sly.language', 'all', $langs);

		// notify listeners
		$this->dispatcher->notify('SLY_CLANG_ADDED', $newLanguage, array('id' => $newLanguage->getId(), 'language' => $newLanguage));

		return $newLanguage;
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Language  $language
	 * @return int
	 */
	public function deleteByLanguage(sly_Model_Language $language) {
		return $this->deleteById($language->getId());
	}

	/**
	 * @param  array $where
	 * @return int
	 */
	public function delete($where) {
		// find all languages first
		$toDelete = $this->find($where);

		// remove
		$res = false;
		$db  = $this->getPersistence();
		$pre = $db->getPrefix();
		$trx = $db->beginTrx();

		try {
			// delete
			$res = parent::delete($where);

			foreach ($toDelete as $language) {
				$params = array('clang' => $language->getId());

				$db->query('DELETE FROM '.$pre.'slice WHERE id IN (SELECT slice_id FROM '.$pre.'article_slice WHERE clang = :clang)', $params);
				$db->delete('article_slice', $params);
				$db->delete('article', $params);

				$this->dispatcher->notify('SLY_CLANG_DELETED', $language, array(
					'id'   => $language->getId(),
					'name' => $language->getName()
				));
			}

			$db->commitTrx($trx);
		}
		catch (Exception $e) {
			$db->rollBackTrx($trx, $e);
		}

		sly_Core::clearCache(array('reason' => 'SLY_CLANG_DELETED'));

		return $res;
	}
}
