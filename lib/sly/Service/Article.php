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
class sly_Service_Article extends sly_Service_ArticleBase {
	protected $sliceService;    ///< sly_Service_Slice
	protected $artSliceService; ///< sly_Service_ArticleSlice
	protected $tplService;      ///< sly_Service_Template

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence       $persistence
	 * @param BabelCache_Interface     $cache
	 * @param sly_Event_IDispatcher    $dispatcher
	 * @param sly_Service_Language     $lngService
	 * @param sly_Service_Slice        $sliceService
	 * @param sly_Service_ArticleSlice $artSliceService
	 * @param sly_Service_Template     $tplService
	 */
	public function __construct(
		sly_DB_Persistence $persistence, BabelCache_Interface $cache, sly_Event_IDispatcher $dispatcher,
		sly_Service_Language $lngService, sly_Service_Slice $sliceService, sly_Service_ArticleSlice $artSliceService,
		sly_Service_Template $tplService
	) {
		parent::__construct($persistence, $cache, $dispatcher, $lngService);

		$this->sliceService    = $sliceService;
		$this->artSliceService = $artSliceService;
		$this->tplService      = $tplService;
	}

	/**
	 * @return string
	 */
	protected function getModelType() {
		return 'article';
	}

	protected function getSiblingQuery($categoryID, $clang = null) {
		$categoryID = (int) $categoryID;
		$where      = '((re_id = '.$categoryID.' AND startpage = 0) OR id = '.$categoryID.')';

		if ($clang !== null) {
			$clang = (int) $clang;
			$where = "$where AND clang = $clang";
		}

		return $where;
	}

	public function getMaxPosition($categoryID) {
		$db     = $this->getPersistence();
		$where  = $this->getSiblingQuery($categoryID);
		$maxPos = $db->magicFetch('article', 'MAX(pos)', $where);

		return $maxPos;
	}

	protected function buildModel(array $params) {
		if ($params['parent']) {
			$cat     = $this->findById($params['parent'], $params['clang']);
			$catname = $cat->getName();
		}
		else {
			$catname = '';
		}

		return new sly_Model_Article(array(
			        'id' => $params['id'],
			     're_id' => $params['parent'],
			      'name' => $params['name'],
			   'catname' => $catname,
			    'catpos' => 0,
			'attributes' => '',
			 'startpage' => 0,
			       'pos' => $params['position'],
			      'path' => $params['path'],
			    'status' => $params['status'],
			      'type' => $params['type'],
			     'clang' => $params['clang'],
			  'revision' => 0
		));
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Article
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
	}

	/**
	 * @param  int $articleID
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public function findById($articleID, $clangID = null) {
		return parent::findById($articleID, $clangID);
	}

	/**
	 * @throws sly_Exception
	 * @param  int            $categoryID
	 * @param  string         $name
	 * @param  int            $status
	 * @param  int            $position
	 * @param  sly_Model_User $user        creator or null for the current user
	 * @return int
	 */
	public function add($categoryID, $name, $status, $position = -1, sly_Model_User $user = null) {
		return $this->addHelper($categoryID, $name, $status, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  int            $articleID
	 * @param  int            $clangID
	 * @param  string         $name
	 * @param  int            $position
	 * @param  sly_Model_User $user       updateuser or null for the current user
	 * @return boolean
	 */
	public function edit($articleID, $clangID, $name, $position = false, sly_Model_User $user = null) {
		return $this->editHelper($articleID, $clangID, $name, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Base_Article  $article
	 * @return boolean
	 */
	public function deleteByArticle(sly_Model_Base_Article $article) {
		return $this->deleteById($article->getId());
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $articleID
	 * @return boolean
	 */
	public function deleteById($articleID) {
		$articleID = (int) $articleID;
		$this->checkForSpecialArticle($articleID);

		// check if article exists

		$article = $this->findById($articleID);

		if ($article === null) {
			throw new sly_Exception(t('article_not_found', $articleID));
		}

		// allow external code to stop the delete operation
		$this->dispatcher->notify('SLY_PRE_ART_DELETE', $article);

		// re-position all following articles
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$parent = $article->getCategoryId();

			foreach ($this->lngService->findAll(true) as $clangID) {
				$pos       = $this->findById($articleID, $clangID)->getPosition();
				$followers = $this->getFollowerQuery($parent, $clangID, $pos);

				$this->moveObjects('-', $followers);
			}

			// delete article and its content
			$sql->delete('article', array('id' => $articleID));
			$sql->delete('article_slice', array('article_id' => $articleID));

			$this->deleteCache($articleID);

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

		// notify system about the deleted article
		$this->dispatcher->notify('SLY_ART_DELETED', $article);

		return true;
	}

	/**
	 * @param  int     $categoryId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clangId
	 * @return array
	 */
	public function findArticlesByCategory($categoryId, $ignore_offlines = false, $clangId = null) {
		return $this->findElementsInCategory($categoryId, $ignore_offlines, $clangId);
	}

	/**
	 * @param  string  $type
	 * @param  boolean $ignore_offlines
	 * @param  int     $clangId
	 * @return array
	 */
	public function findArticlesByType($type, $ignore_offlines = false, $clangId = null) {
		if ($clangId === false || $clangId === null) {
			$clangId = sly_Core::getCurrentClang();
		}

		$type      = trim($type);
		$clangId   = (int) $clangId;
		$namespace = 'sly.article.list';
		$key       = 'artsbytype_'.$type.'_'.$clangId.'_'.($ignore_offlines ? '1' : '0');
		$alist     = $this->cache->get($namespace, $key, null);

		if ($alist === null) {
			$alist = array();
			$sql   = $this->getPersistence();
			$where = array('type' => $type, 'clang' => $clangId);

			if ($ignore_offlines) $where['status'] = 1;

			$sql->select($this->tablename, 'id', $where, null, 'pos,name');
			foreach ($sql as $row) $alist[] = (int) $row['id'];

			$this->cache->set($namespace, $key, $alist);
		}

		$artlist = array();

		foreach ($alist as $id) {
			$art = $this->findById($id, $clangId);
			if ($art) $artlist[] = $art;
		}

		return $artlist;
	}

	/**
	 * @param  sly_Model_Article $article
	 * @param  string            $type
	 * @param  sly_Model_User    $user     updateuser or null for the current user
	 * @return boolean
	 */
	public function setType(sly_Model_Article $article, $type, sly_Model_User $user = null) {
		$user      = $this->getActor($user, __METHOD__);
		$oldType   = $article->getType();
		$articleID = $article->getId();
		$curClang  = $article->getClang();
		$langs     = $this->lngService->findAll(true);
		$sql       = $this->getPersistence();
		$ownTrx    = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($langs as $clangID) {
				$article = $this->findById($articleID, $clangID);

				// update the article

				$article->setType($type);
				$article->setUpdateColumns($user);
				$this->update($article);
			}

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
		$article = $this->findById($articleID, $curClang);
		$this->dispatcher->notify('SLY_ART_TYPE', $article, array('old_type' => $oldType, 'user' => $user));

		return true;
	}

	/**
	 * @param sly_Model_Article $article
	 * @param sly_Model_User    $user
	 */
	public function touch(sly_Model_Article $article, sly_Model_User $user) {
		$article->setUpdateColumns($user);
		$this->update($article);
	}

	/**
	 * Copy an article
	 *
	 * The article will be placed at the end of the target category.
	 *
	 * @param  int            $id      article ID
	 * @param  int            $target  target category ID
	 * @param  sly_Model_User $user    creator for copies or null for the current user
	 * @return int                     the new article's ID
	 */
	public function copy($id, $target, sly_Model_User $user = null) {
		$id      = (int) $id;
		$target  = (int) $target;
		$user    = $this->getActor($user, __METHOD__);
		$article = $this->findById($id);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		// check category

		if ($target !== 0 && $this->catService->findById($target) === null) {
			throw new sly_Exception(t('category_not_found', $target));
		}

		// prepare infos

		$sql   = $this->getPersistence();
		$pos   = $this->getMaxPosition($target) + 1;
		$newID = $sql->magicFetch('article', 'MAX(id)') + 1;

		// copy by language
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($this->lngService->findAll(true) as $clang) {
				$source    = $this->findById($id, $clang);
				$cat       = $target === 0 ? null : $this->catService->findById($target, $clang);
				$duplicate = clone $source;

				$duplicate->setId($newID);
				$duplicate->setParentId($target);
				$duplicate->setCatName($cat ? $cat->getName() : '');
				$duplicate->setPosition($pos);
				$duplicate->setStatus(0);
				$duplicate->setPath($cat ? ($cat->getPath().$target.'|') : '|');
				$duplicate->setUpdateColumns($user);
				$duplicate->setCreateColumns($user);

				// make sure that when copying start articles
				// we actually create an article and not a category
				$duplicate->setStartpage(0);
				$duplicate->setCatPosition(0);

				// store it
				$sql->insert($this->tablename, array_merge($duplicate->getPKHash(), $duplicate->toHash()));
				$this->deleteListCache();

				// copy slices
				if ($source->hasType()) {
					$this->copyContent($id, $newID, $clang, $clang);
				}

				// notify system
				$this->dispatcher->notify('SLY_ART_COPIED', $duplicate, compact('source', 'user'));
			}

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

		return $newID;
	}

	/**
	 * Move an article
	 *
	 * The article will be placed at the end of the target category.
	 *
	 * @param int            $id      article ID
	 * @param int            $target  target category ID
	 * @param sly_Model_User $user    updateuser or null for the current user
	 */
	public function move($id, $target, sly_Model_User $user = null) {
		$id      = (int) $id;
		$target  = (int) $target;
		$user    = $this->getActor($user, __METHOD__);
		$article = $this->findById($id);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		if ($article->isStartArticle()) {
			throw new sly_Exception(t('use_category_service_to_move_categories'));
		}

		// check category

		if ($target !== 0 && $this->catService->findById($target) === null) {
			throw new sly_Exception(t('category_not_found', $target));
		}

		$source = (int) $article->getCategoryId();

		if ($source === $target) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		// prepare infos

		$pos    = $this->getMaxPosition($target) + 1;
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($this->lngService->findAll(true) as $clang) {
				$article = $this->findById($id, $clang);
				$cat     = $target === 0 ? null : $this->catService->findById($target, $clang);
				$moved   = clone $article;

				$moved->setParentId($target);
				$moved->setPath($cat ? $cat->getPath().$target.'|' : '|');
				$moved->setCatName($cat ? $cat->getName() : '');
				$moved->setStatus(0);
				$moved->setPosition($pos);
				$moved->setUpdateColumns($user);

				// move article at the end of new category
				$this->update($moved);

				// re-number old category
				$followers = $this->getFollowerQuery($source, $clang, $article->getPosition());
				$this->moveObjects('-', $followers);

				// notify system
				$this->dispatcher->notify('SLY_ART_MOVED', $id, array(
					'clang'  => $clang,
					'target' => $target,
					'user'   => $user
				));
			}

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

	/**
	 * Converts an article to the it's own category start article
	 *
	 * The article will be converted to an category and all articles and
	 * categories will be moved to be its children.
	 *
	 * @param int            $articleID  article ID
	 * @param sly_Model_User $user       updateuser or null for the current user
	 */
	public function convertToStartArticle($articleID, sly_Model_User $user = null) {
		$articleID = (int) $articleID;
		$user      = $this->getActor($user, __METHOD__);
		$article   = $this->findById($articleID);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		if ($article->isStartArticle()) {
			throw new sly_Exception(t('article_is_startarticle'));
		}

		if ($article->getCategoryId() === 0) {
			throw new sly_Exception(t('root_articles_cannot_be_startarticles'));
		}

		// switch key params of old and new start articles in every language

		$oldCat  = $article->getCategoryId();
		$newPath = $article->getPath();
		$params  = array('path', 'catname', 'startpage', 'catpos', 're_id');

		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($this->lngService->findAll(true) as $clang) {
				$newStarter = $this->findById($articleID, $clang)->toHash();
				$oldStarter = $this->findById($oldCat, $clang)->toHash();

				foreach ($params as $param) {
					$t = $newStarter[$param];
					$newStarter[$param] = $oldStarter[$param];
					$oldStarter[$param] = $t;
				}

				$oldStarter['clang'] = $clang;
				$newStarter['clang'] = $clang;
				$oldStarter['id']    = $oldCat;
				$newStarter['id']    = $articleID;

				$this->update(new sly_Model_Article($oldStarter));
				$this->update(new sly_Model_Article($newStarter));
			}

			// switch parent id and adjust paths

			$prefix = sly_Core::getTablePrefix();

			$sql->update('article', array('re_id' => $articleID), array('re_id' => $oldCat));
			$sql->query('UPDATE '.$prefix.'article SET path = REPLACE(path, "|'.$oldCat.'|", "|'.$articleID.'|") WHERE path LIKE "%|'.$oldCat.'|%"');

			// clear cache

			$this->clearCacheByQuery(array('re_id' => $articleID));
			$this->deleteListCache();

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
		$this->dispatcher->notify('SLY_ART_TO_STARTPAGE', $articleID, array('old_cat' => $oldCat, 'user' => $user));
	}

	/**
	 * Copies an article's content to another article
	 *
	 * The copied slices are appended to each matching slot in the target
	 * article. Slots not present in the target are simply skipped. Existing
	 * content remains the same.
	 *
	 * @param int            $srcID     source article ID
	 * @param int            $dstID     target article ID
	 * @param int            $srcClang  source clang
	 * @param int            $dstClang  target clang
	 * @param int            $revision  revision (unused)
	 * @param sly_Model_User $user      author or null for the current user
	 */
	public function copyContent($srcID, $dstID, $srcClang = 0, $dstClang = 0, $revision = 0, sly_Model_User $user = null) {
		$srcClang = (int) $srcClang;
		$dstClang = (int) $dstClang;
		$srcID    = (int) $srcID;
		$dstID    = (int) $dstID;
		$revision = (int) $revision;
		$user     = $this->getActor($user, __METHOD__);

		if ($srcID === $dstID && $srcClang === $dstClang) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		$source = $this->findById($srcID, $srcClang);
		$dest   = $this->findById($dstID, $dstClang);

		// copy the slices by their slots

		$asServ   = $this->artSliceService;
		$sql      = $this->getPersistence();
		$login    = $user->getLogin();
		$srcSlots = $this->tplService->getSlots($source->getTemplateName());
		$dstSlots = $this->tplService->getSlots($dest->getTemplateName());
		$where    = array('article_id' => $srcID, 'clang' => $srcClang, 'revision' => $revision);
		$dstWhere = array('article_id' => $dstID, 'clang' => $dstClang, 'revision' => $revision);
		$changes  = false;

		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($srcSlots as $srcSlot) {
				// skip slots not present in the destination article
				if (!in_array($srcSlot, $dstSlots)) continue;

				// find start position in target article
				$dstWhere['slot'] = $srcSlot;
				$slices           = $asServ->find($dstWhere);
				$position         = count($slices);

				// find slices to copy
				$where['slot'] = $srcSlot;
				$slices        = $asServ->find($where, null, 'pos ASC');

				foreach ($slices as $articleSlice) {
					$slice = $articleSlice->getSlice();
					$slice = $this->sliceService->copy($slice);

					$asServ->create(array(
						'clang'      => $dstClang,
						'slot'       => $srcSlot,
						'pos'        => $position,
						'slice_id'   => $slice->getId(),
						'article_id' => $dstID,
						'revision'   => $revision,
						'createdate' => time(),
						'createuser' => $login,
						'updatedate' => time(),
						'updateuser' => $login
					));

					++$position;
					$changes = true;
				}
			}

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

		if ($changes) {
			$this->deleteCache($dstID, $dstClang);

			// notify system
			$this->dispatcher->notify('SLY_ART_CONTENT_COPIED', null, array(
				'from_id'     => $srcID,
				'from_clang'  => $srcClang,
				'to_id'       => $dstID,
				'to_clang'    => $dstClang,
				'user'        => $user
			));
		}
	}
}
