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
 * @ingroup util
 *
 * @author zozi@webvariants.de
 */
class sly_Util_Article {
	const CURRENT_ARTICLE  = -1; ///< int
	const START_ARTICLE    = -2; ///< int
	const NOTFOUND_ARTICLE = -3; ///< int

	/**
	 * checks whether an article exists or not
	 *
	 * @param  int $articleID
	 * @return boolean
	 */
	public static function exists($articleID) {
		return sly_Core::getContainer()->getArticleService()->exists($articleID);
	}

	/**
	 * @param  mixed $article
	 * @return boolean
	 */
	public static function isValid($article) {
		return is_object($article) && ($article instanceof sly_Model_Article);
	}

	/**
	 * translate the article constants into the currently set article ID
	 *
	 * @param  int $id
	 * @return int      the actual ID (int) or null if an invalid value was given
	 */
	public static function resolveConstant($id) {
		switch ($id) {
			case self::CURRENT_ARTICLE:  return sly_Core::getCurrentArticleId();
			case self::START_ARTICLE:    return sly_Core::getSiteStartArticleId();
			case self::NOTFOUND_ARTICLE: return sly_Core::getNotFoundArticleId();
		}

		return null;
	}

	/**
	 * @throws sly_Exception
	 * @param  int   $articleID
	 * @param  int   $clang
	 * @param  int   $revision
	 * @param  mixed $default
	 * @return sly_Model_Article
	 */
	public static function findById($articleID, $clang = null, $revision = sly_Service_Article::FIND_REVISION_ONLINE, $default = null) {
		$service   = sly_Core::getContainer()->getArticleService();
		$articleID = (int) $articleID;

		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		$article   = $service->findByPK($articleID, $clang, $revision);

		if ($article) return $article;

		$id = self::resolveConstant($default);

		if ($id !== null) {
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
		return self::findById(sly_Core::getSiteStartArticleId(), $clang);
	}

	/**
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public static function findNotFoundArticle($clang = null) {
		return self::findById(sly_Core::getNotFoundArticleId(), $clang);
	}

	/**
	 * @param  int     $categoryID
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public static function findByCategory($categoryID, $ignoreOfflines = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}
		return sly_Core::getContainer()->getArticleService()->findArticlesByCategory($categoryID, $clang, $ignoreOfflines);
	}

	/**
	 * @param  string  $type
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public static function findByType($type, $ignoreOfflines = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}
		return sly_Core::getContainer()->getArticleService()->findArticlesByType($type, $clang, $ignoreOfflines);
	}

	/**
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public static function getRootArticles($ignoreOfflines = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}
		return self::findByCategory(0, $clang, $ignoreOfflines);
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isSiteStartArticle(sly_Model_Article $article) {
		return $article->getId() === sly_Core::getSiteStartArticleId();
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isNotFoundArticle(sly_Model_Article $article) {
		return $article->getId() === sly_Core::getNotFoundArticleId();
	}

	/**
	 * get an article's URL
	 *
	 * @throws UnexpectedValueException
	 * @param  int     $articleID  an existing article's ID or one of the class constants
	 * @param  int     $clang
	 * @param  array   $params
	 * @param  string  $divider
	 * @param  boolean $absolute
	 * @param  boolean $secure     force HTTPS (true) or HTTP (false) URL, use null for the current protocol
	 * @return string
	 */
	public static function getUrl($articleID, $clang = null, $params = array(), $divider = '&amp;', $absolute = false, $secure = null) {
		$article = self::findById($articleID, $clang, sly_Service_Article::FIND_REVISION_ONLINE, $articleID);

		// if no article was found, the default value, an integer (articleID), will be returned
		if (!($article instanceof sly_Model_Article)) {
			$clang     = $clang === null ? sly_Core::getCurrentClang() : (int) $clang;
			$articleID = (int) $articleID;

			throw new UnexpectedValueException('Could not find article '.$articleID.'/'.$clang.' in getUrl().');
		}

		return $absolute
			? sly_Util_HTTP::getAbsoluteUrl($article, $article->getClang(), $params, $divider, $secure)
			: sly_Util_HTTP::getUrl($article, $article->getClang(), $params, $divider, $secure);
	}
}
