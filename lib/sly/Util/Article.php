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
 * @ingroup util
 *
 * @author zozi@webvariants.de
 */
class sly_Util_Article {
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
	 * @param  int $articleId
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public static function findById($articleId, $clang = null) {
		return sly_Service_Factory::getArticleService()->findById($articleId, $clang);
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
	public static function findByCategory($categoryId, $ignore_offlines = false, $clangId = false) {
		return sly_Service_Factory::getArticleService()->findArticlesByCategory($categoryId, $ignore_offlines, $clangId);
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
		static $canReadCache;
		if(!isset($canReadCache[$articleId])) {
			$canReadCache[$articleId] = false;

			if(self::canEditContent($user, $articleId)) $canReadCache[$articleId] = true;

			//check all children for write rights
			$article = self::findById($articleId);
			if ($article) {
				$path = $article->getPath().$article->getId().'|%';
			} else {
				$path = '|%';
			}
			$query  = sly_DB_Persistence::getInstance();
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
			$query->query('SELECT DISTINCT id FROM '.$prefix.'article WHERE path LIKE ?', array($path));
			foreach($query as $row) {
				if(self::canEditContent($user, (int) $row['id'])) $canReadCache[$articleId] = true;
			}
		}
		return $canReadCache[$articleId];
	}

	public static function canEditArticle(sly_Model_User $user, $articleId) {
		if ($user->hasRight('editContentOnly[]')) return false;
		return self::canEditContent($user, $articleId);
	}

	public static function canEditContent(sly_Model_User $user, $articleId) {
		if ($user->isAdmin() || $user->hasRight('csw[0]')) return true;

		return sly_Authorisation::hasPermission($user->getId(), 'csw', $articleId);
	}
}
