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
 * Business Model Klasse für Kategorien
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Category extends sly_Model_Base_Article {
	/**
	 * return the catname
	 *
	 * @return string
	 */
	public function getName() {
		return $this->getCatName();
	}

	/**
	 * return the startarticle of this category
	 *
	 * @return sly_Model_Article
	 */
	public function getStartArticle() {
		$service = sly_Core::getContainer()->getArticleService();

		return $service->findByPK($this->getId(), $this->getClang(), sly_Service_Article::FIND_REVISION_ONLINE);
	}

	/**
	 * return all articles of this category
	 *
	 * @param  boolean $ignoreOfflines
	 * @return array
	 */
	public function getArticles($ignoreOfflines = false) {
		$service = sly_Core::getContainer()->getArticleService();

		return $service->findArticlesByCategory($this->getId(), $this->getClang(), $ignoreOfflines);
	}

	/**
	 * get the parent category
	 *
	 * @param  int $clang
	 * @return sly_Model_Category
	 */
	public function getParent($clang = null) {
		$service = sly_Core::getContainer()->getCategoryService();
		$clang   = $clang === null ? $this->getClang() : $clang;

		return $service->findByPK($this->getParentId(), $clang, sly_Service_Category::FIND_REVISION_ONLINE);
	}

	/**
	 * return true if this is an ancestor of the given category
	 *
	 * @param  sly_Model_Category $otherCat
	 * @return boolean
	 */
	public function isAncestor(sly_Model_Category $otherCat) {
		return in_array($this->getId(), explode('|', $otherCat->getPath()));
	}

	/**
	 * @param  sly_Model_Category $otherCat
	 * @return boolean
	 */
	public function isParent(sly_Model_Category $otherCat) {
		return $this->getId() == $otherCat->getParentId();
	}

	/**
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public function getChildren($ignoreOfflines = false, $clang = null) {
		$service = sly_Core::getContainer()->getCategoryService();
		$clang   = $clang === null ? $this->getClang() : $clang;

		return $service->findByParentId($this->getId(), $ignoreOfflines, $clang);
	}
}
