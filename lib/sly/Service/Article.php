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
class sly_Service_Article extends sly_Service_ArticleBase {
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
		$db     = sly_DB_Persistence::getInstance();
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
	 * @param  int    $categoryID
	 * @param  string $name
	 * @param  int    $status
	 * @param  int    $position
	 * @return int
	 */
	public function add($categoryID, $name, $status, $position = -1) {
		return $this->addHelper($categoryID, $name, $status, $position);
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $articleID
	 * @param  int    $clangID
	 * @param  string $name
	 * @param  int    $position
	 * @return boolean
	 */
	public function edit($articleID, $clangID, $name, $position = false) {
		return $this->editHelper($articleID, $clangID, $name, $position);
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $articleID
	 * @return boolean
	 */
	public function delete($articleID) {
		$articleID = (int) $articleID;
		$this->checkForSpecialArticle($articleID);

		// check if article exists

		$article = $this->findById($articleID);

		if ($article === null) {
			throw new sly_Exception(t('article_not_found', $articleID));
		}

		// re-position all following articles

		$parent = $article->getCategoryId();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$pos       = $this->findById($articleID, $clangID)->getPosition();
			$followers = $this->getFollowerQuery($parent, $clangID, $pos);

			$this->moveObjects('-', $followers);
		}

		// Artikel löschen
		$sql = sly_DB_Persistence::getInstance();
		$sql->delete('article', array('id' => $articleID));
		$sql->delete('article_slice', array('article_id' => $articleID));

		$this->deleteCache($articleID);

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_DELETED', $article);

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
		$alist     = sly_Core::cache()->get($namespace, $key, null);

		if ($alist === null) {
			$alist = array();
			$sql   = sly_DB_Persistence::getInstance();
			$where = array('type' => $type, 'clang' => $clangId);

			if ($ignore_offlines) $where['status'] = 1;

			$sql->select($this->tablename, 'id', $where, null, 'pos,name');
			foreach ($sql as $row) $alist[] = (int) $row['id'];

			sly_Core::cache()->set($namespace, $key, $alist);
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
	 * @return boolean
	 */
	public function setType(sly_Model_Article $article, $type) {
		$oldType   = $article->getType();
		$langs     = sly_Util_Language::findAll(true);
		$articleID = $article->getId();

		foreach ($langs as $clangID) {
			$article = sly_Util_Article::findById($articleID, $clangID);

			// update the article

			$article->setType($type);
			$article->setUpdateColumns();
			$this->update($article);
		}

		// notify system

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_TYPE', $article, array('old_type' => $oldType));

		return true;
	}

	/**
	 * @param sly_Model_Article $article
	 * @param sly_Model_User    $user
	 */
	public function touch(sly_Model_Article $article, sly_Model_User $user) {
		$article->setUpdateColumns($user->getLogin());
		$this->update($article);
	}

	/**
	 * Copy an article
	 *
	 * The article will be placed at the end of the target category.
	 *
	 * @param  int $id      article ID
	 * @param  int $target  target category ID
	 * @return int          the new article's ID
	 */
	public function copy($id, $target) {
		$id      = (int) $id;
		$target  = (int) $target;
		$article = $this->findById($id);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		// check category

		$cats = sly_Service_Factory::getCategoryService();

		if ($target !== 0 && $cats->findById($target) === null) {
			throw new sly_Exception(t('category_not_found'));
		}

		// prepare infos

		$sql   = sly_DB_Persistence::getInstance();
		$pos   = $this->getMaxPosition($target) + 1;
		$newID = $sql->magicFetch('article', 'MAX(id)') + 1;
		$disp  = sly_Core::dispatcher();

		// copy by language

		foreach (sly_Util_Language::findAll(true) as $clang) {
			$source    = $this->findById($id, $clang);
			$cat       = $target === 0 ? null : $cats->findById($target, $clang);
			$duplicate = clone $source;

			$duplicate->setId($newID);
			$duplicate->setParentId($target);
			$duplicate->setCatName($cat ? $cat->getName() : '');
			$duplicate->setPosition($pos);
			$duplicate->setStatus(0);
			$duplicate->setPath($cat ? ($cat->getPath().$target.'|') : '|');
			$duplicate->setUpdateColumns();
			$duplicate->setCreateColumns();

			// make sure that when copying start articles
			// we actually create an article and not a category
			$duplicate->setStartpage(0);
			$duplicate->setCatPosition(0);

			// store it
			$sql->insert($this->tablename, array_merge($duplicate->getPKHash(), $duplicate->toHash()));
			$this->deleteListCache();

			// copy slices
			$this->copyContent($id, $newID, $clang, $clang);

			// notify system
			$disp->notify('SLY_ART_COPIED', $duplicate, compact('source'));
		}

		return $newID;
	}

	/**
	 * Move an article
	 *
	 * The article will be placed at the end of the target category.
	 *
	 * @param int $id      article ID
	 * @param int $target  target category ID
	 */
	public function move($id, $target) {
		$id      = (int) $id;
		$target  = (int) $target;
		$article = $this->findById($id);

		// check article

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		if ($article->isStartArticle()) {
			throw new sly_Exception(t('use_category_service_to_move_categories'));
		}

		// check category

		$cats = sly_Service_Factory::getCategoryService();

		if ($target !== 0 && $cats->findById($target) === null) {
			throw new sly_Exception(t('category_not_found'));
		}

		$source = (int) $article->getCategoryId();

		if ($source === $target) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		// prepare infos

		$pos  = $this->getMaxPosition($target) + 1;
		$disp = sly_Core::dispatcher();

		foreach (sly_Util_Language::findAll(true) as $clang) {
			$article = $this->findById($id, $clang);
			$cat     = $target === 0 ? null : $cats->findById($target, $clang);
			$moved   = clone $article;

			$moved->setParentId($target);
			$moved->setPath($cat ? $cat->getPath().$target.'|' : '|');
			$moved->setCatName($cat ? $cat->getName() : '');
			$moved->setStatus(0);
			$moved->setPosition($pos);
			$moved->setUpdateColumns();

			// move article at the end of new category
			$this->update($moved);

			// re-number old category
			$followers = $this->getFollowerQuery($source, $clang, $article->getPosition());
			$this->moveObjects('-', $followers);

			// notify system
			$disp->notify('SLY_ART_MOVED', $id, array(
				'clang'  => $clang,
				'target' => $target
			));
		}
	}

	/**
	 * Converts an article to the it's own category start article
	 *
	 * The article will be converted to an category and all articles and
	 * categories will be moved to be its children.
	 *
	 * @param int $articleID  article ID
	 */
	public function convertToStartArticle($articleID) {
		$articleID = (int) $articleID;
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

		foreach (sly_Util_Language::findAll(true) as $clang) {
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
		$sql    = sly_DB_Persistence::getInstance();

		$sql->update('article', array('re_id' => $articleID), array('re_id' => $oldCat));
		$sql->query('UPDATE '.$prefix.'article SET path = REPLACE(path, "|'.$oldCat.'|", "|'.$articleID.'|") WHERE path LIKE "%|'.$oldCat.'|%"');

		// clear cache

		$this->clearCacheByQuery(array('re_id' => $articleID));
		$this->deleteListCache();

		// notify system

		sly_Core::dispatcher()->notify('SLY_ART_TO_STARTPAGE', $articleID, array('old_cat' => $oldCat));
	}

	/**
	 * Copies an article's content to another article
	 *
	 * The copied slices are appended to each matching slot in the target
	 * article. Slots not present in the target are simply skipped. Existing
	 * content remains the same.
	 *
	 * @param int $srcID     source article ID
	 * @param int $dstID     target article ID
	 * @param int $srcClang  source clang
	 * @param int $dstClang  target clang
	 * @param int $revision  revision (unused)
	 */
	public function copyContent($srcID, $dstID, $srcClang = 0, $dstClang = 0, $revision = 0) {
		$srcClang = (int) $srcClang;
		$dstClang = (int) $dstClang;
		$srcID    = (int) $srcID;
		$dstID    = (int) $dstID;
		$revision = (int) $revision;

		if ($srcID === $dstID && $srcClang === $dstClang) {
			throw new sly_Exception(t('source_and_target_are_equal'));
		}

		$source = $this->findById($srcID, $srcClang);
		$dest   = $this->findById($srcID, $srcClang);

		// copy the slices by their slots

		$sServ      = sly_Service_Factory::getSliceService();
		$asServ     = sly_Service_Factory::getArticleSliceService();
		$tplService = sly_Service_Factory::getTemplateService();
		$sql        = sly_DB_Persistence::getInstance();
		$user       = sly_Util_User::getCurrentUser();
		$login      = $user ? $user->getLogin() : '';
		$srcSlots   = $tplService->getSlots($source->getTemplateName());
		$dstSlots   = $tplService->getSlots($dest->getTemplateName());
		$where      = array('article_id' => $srcID, 'clang' => $srcClang, 'revision' => $revision);
		$changes    = false;

		foreach ($srcSlots as $srcSlot) {
			// skip slots not present in the destination article
			if (!in_array($srcSlot, $dstSlots)) continue;

			$where['slot'] = $srcSlot;
			$slices        = $asServ->find($where);
			$position      = 0;

			foreach ($slices as $articleSlice) {
				$sql->beginTransaction();

				$slice = $articleSlice->getSlice();
				$slice = $sServ->copy($slice);

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

				$sql->commit();

				++$position;
				$changes = true;
			}
		}

		if ($changes) {
			$this->deleteCache($dstID, $dstClang);

			// notify system
			sly_Core::dispatcher()->notify('SLY_ART_CONTENT_COPIED', null, array(
				'from_id'     => $srcID,
				'from_clang'  => $srcClang,
				'to_id'       => $dstID,
				'to_clang'    => $dstClang,
			));
		}
	}
}
