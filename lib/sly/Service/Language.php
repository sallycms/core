<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
class sly_Service_Language extends sly_Service_Model_Base_Id {
	protected $tablename = 'clang'; ///< string
	protected $cache;               ///< BabelCache_Interface
	protected $dispatcher;          ///< sly_Event_IDispatcher

	/**
	 * Constructor
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
		$this->dispatcher->notify('CLANG_UPDATED', $model);

		return $result;
	}

	/**
	 * @throws Exception           if something goes wrong
	 * @param  array $params
	 * @return sly_Model_Language
	 */
	public function create($params) {
		$langs = sly_Util_Language::findAll(); // TODO: avoid wrapper around ourselves

		// if there are no languages yet, don't attempt to copy anything

		if (count($langs) === 0) {
			$newLanguage = parent::create($params);
		}
		else {
			$sql    = $this->getPersistence();
			$ownTrx = !$sql->isTransRunning();

			if ($ownTrx) {
				$sql->beginTransaction();
			}

			try {
				$newLanguage = parent::create($params);
				$sourceID    = sly_Core::getDefaultClangId();

				// if a bad default language was configured, use the first one
				if (!isset($langs[$sourceID])) {
					$ids = array_keys($langs);
					sort($ids);
					$sourceID = reset($ids);
				}

				$sql->query(str_replace('~', sly_Core::getTablePrefix(),
					'INSERT INTO ~article (id,re_id,name,catname,catpos,attributes,'.
					'startpage,pos,path,status,createdate,updatedate,type,clang,createuser,'.
					'updateuser,revision) '.
					'SELECT id,re_id,name,catname,catpos,attributes,startpage,pos,path,0,createdate,'.
					'updatedate,type,?,createuser,updateuser,revision '.
					'FROM ~article WHERE clang = ?'),
					array($newLanguage->getId(), $sourceID)
				);

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
		}

		// update cache before notifying the listeners (so that they can call findAll() and get fresh data)
		$langs[$newLanguage->getId()] = $newLanguage;
		$this->cache->set('sly.language', 'all', $langs);

		// notify listeners
		$this->dispatcher->notify('CLANG_ADDED', $newLanguage, array('id' => $newLanguage->getId(), 'language' => $newLanguage));

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
		$db = $this->getPersistence();

		// find all languages first
		$toDelete = $this->find($where);
		$allLangs = sly_Util_Language::findAll(); // TODO: avoid wrapper around ourselves

		// delete
		$res = parent::delete($where);

		// update cache (so that addOns can access fresh clang data when listening to CLANG_DELETED)
		foreach ($toDelete as $language) {
			unset($allLangs[$language->getId()]);
		}

		$this->cache->set('sly.language', 'all', $allLangs);

		// remove
		$db     = $this->getPersistence();
		$ownTrx = !$db->isTransRunning();

		if ($ownTrx) {
			$db->beginTransaction();
		}

		try {
			foreach ($toDelete as $language) {
				$params = array('clang' => $language->getId());
				$db->delete('article', $params);
				$db->delete('article_slice', $params);

				$this->dispatcher->notify('CLANG_DELETED', $language, array(
					'id'   => $language->getId(),
					'name' => $language->getName()
				));
			}

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

		sly_Core::clearCache();
		return $res;
	}
}
