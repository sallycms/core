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
		return $this->getCatname();
	}

	/**
	 * return the startarticle of this category
	 * 
	 * @return sly_Model_Article
	 */
	public function getStartArticle() {
		return sly_Util_Article::findById($this->getId(), $this->getClang());
	}

	/**
	 * return all articles of this category 
	 * 
	 * @param  boolean $ignore_offlines
	 * @return array 
	 */
	public function getArticles($ignore_offlines = false) {
		return sly_Util_Article::findByCategory($this->getId(), $ignore_offlines, $this->getClang());
	}

	/**
	 * get the parent category
	 *
	 * @param  int $clang
	 * @return sly_Model_Category 
	 */
	public function getParent($clang = null) {
		return sly_Util_Category::findById($this->getParentId(), $clang);
	}

	/**
	 * return true if this is an ancestor of the given category
	 * 
	 * @param  sly_Model_Category $other_cat
	 * @return boolean
	 */
	public function isAncestor(sly_Model_Category $other_cat) {
		return in_array($this->getId(), explode('|', $other_cat->getPath()));
	}

	/**
	 *
	 * @param  sly_Model_Category $other_cat
	 * @return boolean 
	 */
	public function isParent(sly_Model_Category $other_cat) {
		return $this->getId() == $other_cat->getParentId();
	}
	
	/**
	 *
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array 
	 */
	public function getChildren($ignore_offlines = false, $clang = null) {
		if ($clang === null) $clang = $this->getClang();
		return sly_Util_Category::findByParentId($this->getId(), $ignore_offlines, $clang);
	}

}
