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
 * @ingroup util
 *
 * @author zozi@webvariants.de
 */
class sly_Util_Article {
	const CURRENT_ARTICLE  = -1; ///< int
	const START_ARTICLE    = -2; ///< int
	const NOTFOUND_ARTICLE = -3; ///< int

	/**
	 * checks wheter an article exists or not
	 *
	 * @param  int $articleId
	 * @return boolean
	 */
	public static function exists($articleId) {
		return self::isValid(self::findById($articleId));
	}

	/**
	 * @param  mixed $article
	 * @return boolean
	 */
	public static function isValid($article) {
		return is_object($article) && ($article instanceof sly_Model_Article);
	}

	/**
	 * @param  int   $articleId
	 * @param  int   $clang
	 * @param  mixed $default
	 * @return sly_Model_Article
	 */
	public static function findById($articleId, $clang = null, $default = null) {
		$service   = sly_Service_Factory::getArticleService();
		$articleId = (int) $articleId;
		$article   = $service->findById($articleId, $clang);

		if ($article) return $article;

		switch ($default) {
			case self::CURRENT_ARTICLE:  $id = sly_Core::getCurrentArticleId();   break;
			case self::START_ARTICLE:    $id = sly_Core::getSiteStartArticleId(); break;
			case self::NOTFOUND_ARTICLE: $id = sly_Core::getNotFoundArticleId();  break;
			// no default case by design
		}

		if (isset($id)) {
			$article = $service->findById($id, $clang);
			if ($article) return $article;
			throw new sly_Exception('Could not find a matching article, giving up.');
		}

		return $default;
	}

	/**
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public static function findSiteStartArticle($clang = null) {
		return self::findById(sly_core::getSiteStartArticleId(), $clang);
	}

	/**
	 * @param  int     $categoryId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clangId
	 * @return array
	 */
	public static function findByCategory($categoryId, $ignore_offlines = false, $clangId = null) {
		return sly_Service_Factory::getArticleService()->findArticlesByCategory($categoryId, $ignore_offlines, $clangId);
	}

	/**
	 * @param  string  $type
	 * @param  boolean $ignore_offlines
	 * @param  int     $clangId
	 * @return array
	 */
	public static function findByType($type, $ignore_offlines = false, $clangId = null) {
		return sly_Service_Factory::getArticleService()->findArticlesByType($type, $ignore_offlines, $clangId);
	}

	/**
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array
	 */
	public static function getRootArticles($ignore_offlines = false, $clang = false) {
		return self::findByCategory(0, $ignore_offlines, $clang);
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isSiteStartArticle(sly_Model_Article $article) {
		return $article->getId() == sly_Core::getSiteStartArticleId();
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isNotFoundArticle(sly_Model_Article $article) {
		return $article->getId() == sly_Core::getNotFoundArticleId();
	}

	public static function canReadArticle(sly_Model_User $user, $articleId) {
		return sly_Util_Category::canReadCategory($user, $articleId);
	}

	public static function canEditArticle(sly_Model_User $user, $articleId) {
		if ($user->isAdmin()) return true;
		return $user->hasRight('article', 'edit', $articleId);
	}

	public static function canEditContent(sly_Model_User $user, $articleId) {
		if ($user->isAdmin()) return true;
		return $user->hasRight('article', 'editcontent', $articleId);
	}

	public static function getUrl($articleId, $clang = null, $params = array(), $divider = '&amp;', $absolute = false, $secure = null) {
		$article = self::findById($articleId, $clang);

		if (!$article) {
			throw new UnexpectedValueException('Could not detect the URL target given by "'.$target.'" in getUrl().');
		}

		return $absolute
			? sly_Util_HTTP::getAbsoluteUrl($article, $article->getClang(), $params, $divider, $secure)
			: sly_Util_HTTP::getUrl($article, $article->getClang(), $params, $divider, $secure);
	}
}
