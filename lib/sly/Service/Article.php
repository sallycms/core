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
 * @author  christoph@webvariants.de, zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_Article extends sly_Service_ArticleManager {
	private $urlCache = array();

	/**
	 * @return string
	 */
	protected function getModelType() {
		return 'article';
	}

	/**
	 * get WHERE statement for all category siblings
	 *
	 * @param  int     $categoryID
	 * @param  int     $clang       clang or null for none (*not* the current one)
	 * @param  boolean $asArray
	 * @return mixed                the condition either as an array or as a string
	 */
	protected function getSiblingQuery($categoryID, $clang = null) {
		$categoryID = (int) $categoryID;
		$where      = '((re_id = '.$categoryID.' AND startpage = 0) OR id = '.$categoryID.')';

		if ($clang !== null) {
			$clang = (int) $clang;
			$where = "$where AND clang = $clang";
		}

		return $where;
	}

	protected function buildModel(array $params) {
		if ($params['parent'] && $this->getCategoryService()->exists($params['parent'])) {
			$cat     = $this->getCategoryService()->findByPK($params['parent'], $params['clang'], self::FIND_REVISION_LATEST);
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

	public function getPositionField() {
		return 'pos';
	}

	/**
	 * @param  int $id
	 * @param  int $clang
	 * @param  int $revision
	 * @return sly_Model_Article
	 */
	public function findByPK($id, $clang, $revision = self::FIND_REVISION_ONLINE) {
		return parent::findByPK($id, $clang, $revision);
	}

	/**
	 *
	 * @param  int   $id
	 * @param  int   $clang
	 * @return array
	 */
	public function findAllRevisions($id, $clang) {
		$where  = compact('id', 'clang');
		$order  = 'revision DESC';
		$return = array();
		$db     = $this->getPersistence();

		$db->select($this->getTableName(), '*', $where, null, $order);

		foreach ($db as $row) {
			$item = $this->makeInstance($row);
			$item->setOnline($this->getOnlineStatus($item));
			$return[] = $item;
		}

		return $return;
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
	public function add($categoryID, $name, $position = -1, sly_Model_User $user = null) {
		return $this->addHelper($categoryID, $name, $position, $user);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Article_Base $obj
	 * @param  string                 $name
	 * @param  mixed                  $position
	 * @param  sly_Model_User         $user        updateuser or null for the current user
	 * @return boolean
	 */
	public function edit(sly_Model_Base_Article $obj, $name, $position = false, sly_Model_User $user = null) {
		$obj = $this->touch($obj);
		return $this->editHelper($obj, $name, $position, $user);
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
		if (!$this->exists($articleID)) {
			throw new sly_Exception(t('article_not_found', $articleID));
		}

		$article    = $this->findByPK($articleID, $defaultLang, self::FIND_REVISION_LATEST);
		$dispatcher = $this->getDispatcher();
		$sql        = $this->getPersistence();
		$tableName  = $this->getTableName();
		$parent     = $article->getCategoryId();

		$trx = $sql->beginTrx();

		try {
			// allow external code to stop the delete operation
			$dispatcher->notify('SLY_PRE_ART_DELETE', $article);

			foreach ($this->getLanguages() as $clang) {
				$pos = $this->findByPK($articleID, $clang, self::FIND_REVISION_LATEST)->getPosition();

				// delete article and its content
				$sql->update($tableName, array('deleted' => 1, 'pos' => 0), array('id' => $articleID, 'clang' => $clang));

				// re-position all following articles
				$followers = $this->getFollowerQuery($parent, $clang, $pos);

				$this->moveObjects('-', $followers);
			}

			// notify system about the deleted article
			$dispatcher->notify($this->getEvent('DELETED'), $article);

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}

		return true;
	}

	/**
	 * @param  int     $categoryId
	 * @param  int     $clang
	 * @param  boolean $findOnline
	 * @return array
	 */
	public function findArticlesByCategory($categoryId, $clang, $ignoreOfflines = false ) {
		return $this->findElementsInCategory($categoryId, $clang, $ignoreOfflines);
	}

	/**
	 * @param  string  $type
	 * @param  int     $clang
	 * @param  boolean $findOnline
	 * @return array
	 */
	public function findArticlesByType($type, $clang, $findOnline = false) {
		$type  = trim($type);
		$clang = (int) $clang;
		$where = compact('type', 'clang');

		return $this->find($where, null, null, null, null, null, $findOnline);
	}

	/**
	 * @param  sly_Model_Article $article
	 * @param  string            $type
	 * @param  sly_Model_User    $user     updateuser or null for the current user
	 * @return boolean
	 */
	public function setType(sly_Model_Article $article, $type, sly_Model_User $user = null) {
		$oldType = $article->getType();

		if ($oldType === $type) {
			return true;
		}

		$user = $this->getActor($user, __METHOD__);
		$sql  = $this->getPersistence();
		$trx  = $sql->beginTrx();

		try {
			// create new revision
			$article = $this->touch($article, $user);

			// update the article
			$article->setType($type);
			$this->update($article);

			// notify system
			$this->getDispatcher()->notify('SLY_ART_TYPE', $article, array('old_type' => $oldType, 'user' => $user));

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}

		return true;
	}

	/**
	 * @param sly_Model_Article $article
	 * @param sly_Model_User    $user
	 */
	public function touch(sly_Model_Article $article, sly_Model_User $user = null) {
		$user    = $this->getActor($user, __METHOD__);
		$touched = clone $article;
		$sql     = $this->getPersistence();
		$trx     = $sql->beginTrx();

		try {
			$touched->setRevision($this->getMaxRevision($article) + 1);
			$touched->setCreateColumns($user);
			$touched = $this->insert($touched);

			$this->copyContent($article, $touched, $user);

			$this->getDispatcher()->notify('SLY_ART_TOUCHED', $touched, array(
				'source'  => $article,
			));

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}

		return $touched;
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
		$article     = $this->findByPK($id, $defaultLang, self::FIND_REVISION_LATEST);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		if ($article->isStartArticle()) {
			throw new sly_Exception(t('use_category_service_to_move_categories'));
		}

		// check category

		if ($target !== 0 && !$this->getCategoryService()->exists($target)) {
			throw new sly_Exception(t('category_not_found', $target));
		}

		$source = (int) $article->getCategoryId();

		if ($source === $target) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		// prepare infos

		$dispatcher = $this->getDispatcher();
		$catService = $this->getCategoryService();

		$pos = $this->getMaxPosition($target) + 1;
		$sql = $this->getPersistence();
		$trx = $sql->beginTrx();

		try {
			foreach ($this->getLanguages() as $clang) {
				$article = $this->findByPK($id, $clang, self::FIND_REVISION_LATEST);
				$cat     = $target === 0 ? null : $catService->findByPK($target, $clang, self::FIND_REVISION_LATEST);
				$moved   = clone $article;

				$moved->setParentId($target);
				$moved->setPath($cat ? $cat->getPath().$target.'|' : '|');
				$moved->setCatName($cat ? $cat->getName() : '');
				$moved->setPosition($pos);
				$moved->setUpdateColumns($user);

				// move article at the end of new category
				$this->update($moved);

				// re-number old category
				$followers = $this->getFollowerQuery($source, $clang, $article->getPosition());
				$this->moveObjects('-', $followers);

				// notify system
				$dispatcher->notify('SLY_ART_MOVED', $id, array(
					'clang'  => $clang,
					'target' => $target,
					'user'   => $user
				));
			}

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}
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
		$id     = (int) $id;
		$target = (int) $target;
		$user   = $this->getActor($user, __METHOD__);

		// check article

		if (!$this->exists($id)) {
			throw new sly_Exception(t('article_not_found', $id));
		}

		// check category

		if ($target !== 0 && !$this->getCategoryService()->exists($target)) {
			throw new sly_Exception(t('category_not_found', $target));
		}

		// prepare infos

		$sql        = $this->getPersistence();
		$pos        = $this->getMaxPosition($target) + 1;
		$newID      = $sql->magicFetch('article', 'MAX(id)') + 1;
		$dispatcher = $this->getDispatcher();
		$catService = $this->getCategoryService();

		// copy by language

		$trx = $sql->beginTrx();

		try {
			foreach ($this->getLanguages() as $clang) {
				$source    = $this->findByPK($id, $clang, self::FIND_REVISION_LATEST);
				$cat       = $target === 0 ? null : $catService->findByPK($target, $clang, self::FIND_REVISION_LATEST);
				$duplicate = clone $source;

				$duplicate->setId($newID);
				$duplicate->setParentId($target);
				$duplicate->setCatName($cat ? $cat->getName() : '');
				$duplicate->setPosition($pos);
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

				// copy slices
				if ($source->hasType()) {
					$this->copyContent($source, $duplicate, $user);
				}

				// notify system
				$dispatcher->notify('SLY_ART_COPIED', $duplicate, compact('source', 'user'));
			}

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}

		return $duplicate;
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
		$article     = $this->findByPK($articleID, $defaultLang, self::FIND_REVISION_LATEST);

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

		$oldCat     = $article->getCategoryId();
		$params     = array('path', 'catname', 'catpos', 're_id', 'startpage');
		$sql        = $this->getPersistence();
		$dispatcher = $this->getDispatcher();

		$trx = $sql->beginTrx();

		try {
			$table = $this->getTableName();

			foreach ($this->getLanguages() as $clang) {
				$newStarter = $this->findByPK($articleID, $clang, self::FIND_REVISION_LATEST)->toHash();
				$oldStarter = $this->findByPK($oldCat, $clang, self::FIND_REVISION_LATEST)->toHash();

				foreach ($params as $param) {
					$t = $newStarter[$param];
					$newStarter[$param] = $oldStarter[$param];
					$oldStarter[$param] = $t;
				}

				$sql->update($table, $newStarter, array('id' => $articleID, 'clang' => $clang));
				$sql->update($table, $oldStarter, array('id' => $oldCat, 'clang' => $clang));
			}

			// switch parent id and adjust paths
			$prefix = $sql->getPrefix();
			$sql->update($table, array('re_id' => $articleID), array('re_id' => $oldCat));
			$sql->query('UPDATE '.$prefix.$table.' SET path = REPLACE(path, "|'.$oldCat.'|", "|'.$articleID.'|") WHERE path LIKE "%|'.$oldCat.'|%"');

			// notify system
			$dispatcher->notify('SLY_ART_TO_STARTPAGE', $articleID, array('old_cat' => $oldCat, 'user' => $user));

			$sql->commitTrx($trx);
		}
		catch (Exception $e) {
			$sql->rollBackTrx($trx, $e);
		}
	}

	/**
	 * Copies an article's content to another article
	 *
	 * The copied slices are appended to each matching slot in the target
	 * article. Slots not present in the target are simply skipped. Existing
	 * content remains the same.
	 *
	 * @param sly_Model_Article  $source  source article
	 * @param sly_Model_Article  $dest    target article
	 * @param sly_Model_User     $user    author or null for the current user
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
		$asServ     = $this->container->getArticleSliceService();
		$sServ      = $this->container->getSliceService();
		$sql        = $this->getPersistence();
		$login      = $user->getLogin();
		$dstSlots   = $this->container->getTemplateService()->getSlots($dest->getTemplateName());
		$dispatcher = $this->getDispatcher();

		return $sql->transactional(function() use ($sql, $source, $dstSlots, $dest, $sServ, $asServ, $login, $dispatcher, $user) {
			$slices  = $source->getSlices();
			$changes = false;

			foreach ($slices as $articleSlice) {
				$srcSlot = $articleSlice->getSlot();
				// skip slots not present in the destination article
				if (!in_array($articleSlice->getSlot(), $dstSlots)) continue;

				// find position in target article
				$position = $dest->countSlices($srcSlot);

				// find slices to copy
				$slice = $articleSlice->getSlice();
				$slice = $sServ->copy($slice);

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

			// notify system
			if ($changes) {
				$dispatcher->notify('SLY_ART_CONTENT_COPIED', null, array(
					'from' => $source,
					'to'   => $dest,
					'user' => $user
				));
			}

			return $changes;
		});
	}

	/**
	 * return the url
	 *
	 * @param  sly_Model_Article $article
	 * @param  mixed             $params
	 * @param  string            $divider
	 * @param  boolean           $disableCache
	 * @return string
	 */
	public function getUrl(sly_Model_Article $article, $params = '', $divider = '&amp;', $disableCache = false) {
		$id    = $article->getId();
		$clang = $article->getClang();

		// cache the URLs for this request (unlikely to change)

		$cacheKey = substr(md5($id.'_'.$clang.'_'.json_encode($params).'_'.$divider), 0, 10);

		if (!$disableCache && isset($this->urlCache[$cacheKey])) {
			return $this->urlCache[$cacheKey];
		}

		$dispatcher = $this->getDispatcher();
		$redirect   = $dispatcher->filter('SLY_URL_REDIRECT', $article, array(
			'params'       => $params,
			'divider'      => $divider,
			'disableCache' => $disableCache
		));

		// the listener must return an article (sly_Model_Article or int (ID)) or URL (string) to modify the returned URL

		if ($redirect && $redirect !== $article) {
			if (is_integer($redirect)) {
				$id = $redirect;
			}
			elseif ($redirect instanceof sly_Model_Article) {
				$id    = $redirect->getId();
				$clang = $redirect->getClang();
			}
			else {
				$this->urlCache[$cacheKey] = $redirect;
				return $redirect;
			}
		}

		// check for any fancy URL addOns

		$paramString = sly_Util_HTTP::queryString($params, $divider);
		$url         = $dispatcher->filter('URL_REWRITE', '', array(
			'id'            => $id,
			'clang'         => $clang,
			'params'        => $paramString,
			'divider'       => $divider,
			'disable_cache' => $disableCache
		));

		// if no listener is available, generate plain index.php?article_id URLs

		if (empty($url)) {
			$clangString = '';
			$languages   = $this->getLanguages(true);
			$defClang    = $this->container->getConfig()->get('default_clang_id') ?: reset($languages);

			if (count($languages) > 1 && $clang != $defClang) {
				$clangString = $divider.'clang='.$clang;
			}

			$url = 'index.php?article_id='.$id.$clangString.$paramString;
		}

		$this->urlCache[$cacheKey] = $url;

		return $url;
	}

	public function clearUrlCache() {
		$this->urlCache = array();
	}
}
