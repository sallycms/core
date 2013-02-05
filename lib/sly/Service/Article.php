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
		$where      = '((re_id = '.$categoryID.' AND startpage = 0) OR id = '.$categoryID.') AND deleted = 0';

		if ($clang !== null) {
			$clang = (int) $clang;
			$where = "$where AND clang = $clang";
		}

		return $where;
	}

	public function getMaxPosition($categoryID) {
		$db     = $this->getPersistence();
		$where  = $this->getSiblingQuery($categoryID);
		$maxPos = $db->magicFetch($this->getTableName(), 'MAX(pos)', $where);

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
			   'deleted' => 0,
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

	protected function getMaxRevision(sly_Model_Article $article) {
		return $this->getPersistence()->magicFetch(
				$this->getTableName(),
				'MAX(revision)',
				array('id' => $article->getId(), 'clang' => $article->getClang())
		);
	}

	/**
	 * @param  int $id
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public function findById($id, $clang, $revision = null) {
		return parent::findById($id, $clang, $revision);
	}

	/**
	 *
	 * @param  int   $id
	 * @param  int   $clang
	 * @return array
	 */
	public function findAllRevisions($id, $clang) {
		return $this->find(compact('id', 'clang'), null, 'revision DESC');
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
		$articleID   = (int) $articleID;
		$defaultLang = $this->getDefaultLanguageId();
		$this->checkForSpecialArticle($articleID);

		// check if article exists

		$article = $this->findById($articleID, $defaultLang);

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
			$sql->update($this->getTableName(), array('deleted' => 1, 'pos' => 0), array('id' => $articleID));

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
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clangId
	 * @return array
	 */
	public function findArticlesByCategory($categoryId, $ignoreOfflines = false, $clangId = null) {
		return $this->findElementsInCategory($categoryId, $ignoreOfflines, $clangId);
	}

	/**
	 * @param  string  $type
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clangId
	 * @return array
	 */
	public function findArticlesByType($type, $ignoreOfflines = false, $clangId = null) {
		if ($clangId === false || $clangId === null) {
			$clangId = sly_Core::getCurrentClang();
		}

		$type      = trim($type);
		$clangId   = (int) $clangId;
		$namespace = 'sly.article.list';
		$key       = 'artsbytype_'.$type.'_'.$clangId.'_'.($ignoreOfflines ? '1' : '0');
		$alist     = $this->cache->get($namespace, $key, null);

		if ($alist === null) {
			$alist = array();
			$sql   = $this->getPersistence();
			$where = array('type' => $type, 'clang' => $clangId);

			if ($ignoreOfflines) $where['status'] = 1;

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
		$langs     = $this->lngService->findAll(true);
		$sql       = $this->getPersistence();
		$ownTrx    = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			foreach ($langs as $clangID) {
				$article = $this->findById($articleID, $clangID);
				//create new revision
				$article = $this->touch($article, $user);

				// update the article
				$article->setType($type);
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
		$this->dispatcher->notify('SLY_ART_TYPE', $article, array('old_type' => $oldType, 'user' => $user));

		return true;
	}

	/**
	 * @param sly_Model_Article $article
	 * @param sly_Model_User    $user
	 */
	public function touch(sly_Model_Article $article, sly_Model_User $user = null) {
		$user    = $this->getActor($user, __METHOD__);
		$touched = clone $article;
		$touched->setRevision($this->getMaxRevision($article) + 1);
		$touched->setCreateColumns($user);
		$this->deleteCache($article->getId(), $article->getClang());
		$touched = $this->insert($touched);
		$this->copyContent($article, $touched, $user);

		return $touched;
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
		$id          = (int) $id;
		$target      = (int) $target;
		$user        = $this->getActor($user, __METHOD__);

		// check article

		if (!$this->exists($id)) {
			throw new sly_Exception(t('article_not_found', $id));
		}

		// check category

		if ($target !== 0 && !$this->catService->exists($target)) {
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
				$duplicate->setRevision(0);

				// make sure that when copying start articles
				// we actually create an article and not a category
				$duplicate->setStartpage(0);
				$duplicate->setCatPosition(0);

				// store it
				$this->insert($duplicate);

				$this->deleteListCache();

				// copy slices
				if ($source->hasType()) {
					$this->copyContent($source, $duplicate, $user);
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
		$id          = (int) $id;
		$target      = (int) $target;
		$defaultLang = $this->getDefaultLanguageId();
		$user        = $this->getActor($user, __METHOD__);
		$article     = $this->findById($id, $defaultLang);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		if ($article->isStartArticle()) {
			throw new sly_Exception(t('use_category_service_to_move_categories'));
		}

		// check category

		if ($target !== 0 && !$this->catService->exists($target)) {
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
	 * @param  int            $articleID  article ID
	 * @param  sly_Model_User $user       updateuser or null for the current user
	 * @throws sly_Exception
	 */
	public function convertToStartArticle($articleID, sly_Model_User $user = null) {
		$articleID   = (int) $articleID;
		$defaultLang = $this->getDefaultLanguageId();
		$user        = $this->getActor($user, __METHOD__);
		$article     = $this->findById($articleID, $defaultLang);

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
		$params  = array('path', 'catname', 'catpos', 're_id', 'startpage');

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

				$sql->update($this->tablename, $newStarter, array('id' => $articleID, 'clang' => $clang));
				$sql->update($this->tablename, $oldStarter, array('id' => $oldCat, 'clang' => $clang));
				//$this->update(new sly_Model_Article($newStarter));
			}

			// switch parent id and adjust paths

			$prefix = $sql->getPrefix();
			$sql->update($this->tablename, array('re_id' => $articleID), array('re_id' => $oldCat));
			$sql->query('UPDATE '.$prefix.$this->tablename.' SET path = REPLACE(path, "|'.$oldCat.'|", "|'.$articleID.'|") WHERE path LIKE "%|'.$oldCat.'|%"');

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
	 * @param sly_Model_Article  $source    source article
	 * @param sly_Model_Article  $dest      target article
	 * @param sly_Model_User     $user      author or null for the current user
	 */
	public function copyContent(sly_Model_Article $source, sly_Model_Article $dest, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		if (!array_diff_assoc($source->getPKHash(), $dest->getPKHash())) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		if (!$source->hasTemplate() || !$dest->hasTemplate()) {
			return false;
		}

		// copy the slices by their slots
		$asServ   = $this->artSliceService;
		$sql      = $this->getPersistence();
		$login    = $user->getLogin();
		$dstSlots = $this->tplService->getSlots($dest->getTemplateName());
		$changes  = false;

		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$slices = $source->getSlices();

			foreach ($slices as $articleSlice) {
				$srcSlot = $articleSlice->getSlot();
				// skip slots not present in the destination article
				if (!in_array($articleSlice->getSlot(), $dstSlots)) continue;

				// find position in target article
				$position = $dest->countSlices($srcSlot);

				// find slices to copy
				$slice = $articleSlice->getSlice();
				$slice = $this->sliceService->copy($slice);

				$aSlice = new Sly_Model_ArticleSlice(array(
					'clang'      => $dest->getClang(),
					'slot'       => $srcSlot,
					'pos'        => $position,
					'slice_id'   => $slice->getId(),
					'article_id' => $dest->getId(),
					'revision'   => $dest->getRevision(),
					'createdate' => time(),
					'createuser' => $login,
					'updatedate' => time(),
					'updateuser' => $login
				));

				$asServ->insert($aSlice);

				$changes = true;
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
			$this->deleteCache($dest->getId(), $dest->getClang());

			// notify system
			$this->dispatcher->notify('SLY_ART_CONTENT_COPIED', null, array(
				'from'     => $source,
				'to'       => $dest,
				'user'     => $user
			));
		}
	}
}
