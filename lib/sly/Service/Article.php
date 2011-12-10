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

	protected function getMaxPrior($categoryID) {
		$db     = sly_DB_Persistence::getInstance();
		$where  = $this->getSiblingQuery($categoryID);
		$maxPos = $db->magicFetch('article', 'MAX(prior)', $where);

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
			  'catprior' => 0,
			'attributes' => '',
			 'startpage' => 0,
			     'prior' => $params['position'],
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
			throw new sly_Exception(t('no_such_article'));
		}

		// re-position all following articles

		$parent = $article->getCategoryId();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$prior     = $this->findById($articleID, $clangID)->getPrior();
			$followers = $this->getFollowerQuery($parent, $clangID, $prior);

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
			$where = array('type' => $type, 'clang' => $clangId, 'startpage' => 0);

			if ($ignore_offlines) $where['status'] = 1;

			$sql->select($this->tablename, 'id', $where, null, 'prior,name');
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
		$article->setUpdatedate(time());
		$article->setUpdateuser($user->getLogin());
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
		$id     = (int) $id;
		$target = (int) $target;

		// check article

		if ($this->findById($id) === null) {
			throw new sly_Exception(t('no_such_article'));
		}

		// check category

		$cats = sly_Service_Factory::getCategoryService();

		if ($target !== 0 && $cats->findById($target) === null) {
			throw new sly_Exception(t('no_such_category'));
		}

		// prepare infos

		$sql   = sly_DB_Persistence::getInstance();
		$pos   = $this->getMaxPrior($target) + 1;
		$newID = $sql->magicFetch('article', 'MAX(id)') + 1;

		// copy by language

		foreach (sly_Util_Language::findAll(true) as $clang) {
			$source    = $this->findById($id, $clang);
			$cat       = $target === 0 ? null : $cats->findById($target, $clang);
			$duplicate = clone $source;

			$duplicate->setId($newID);
			$duplicate->setParentId($target);
			$duplicate->setCatname($cat ? $cat->getName() : '');
			$duplicate->setPrior($pos);
			$duplicate->setStatus(0);
			$duplicate->setPath($cat ? ($cat->getPath().$target.'|') : '|');
			$duplicate->setUpdateColumns();
			$duplicate->setCreateColumns();

			// store it
			$sql->insert($this->tablename, array_merge($duplicate->getPKHash(), $duplicate->toHash()));
			$this->deleteListCache();

			// copy slices
			rex_copyContent($id, $newID, $clang, $clang);

			// notify system
			sly_Core::dispatcher()->notify('SLY_ART_COPIED', $duplicate);
		}

		return $newID;
	}
}
