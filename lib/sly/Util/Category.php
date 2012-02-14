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
class sly_Util_Category {
	const CURRENT_ARTICLE  = -1; ///< int
	const START_ARTICLE    = -2; ///< int
	const NOTFOUND_ARTICLE = -3; ///< int

	/**
	 * checks wheter a category exists or not
	 *
	 * @param  int $categoryId
	 * @return boolean
	 */
	public static function exists($categoryId) {
		return self::isValid(self::findById($categoryId));
	}

	/**
	 * @param sly_Model_Category $category
	 * @return boolean
	 */
	public static function isValid($category) {
		return is_object($category) && ($category instanceof sly_Model_Category);
	}

	/**
	 * @param  int   $articleId
	 * @param  int   $clang
	 * @param  mixed $default
	 * @return sly_Model_Category
	 */
	public static function findById($categoryId, $clang = null, $default = null) {
		$service    = sly_Service_Factory::getCategoryService();
		$categoryId = (int) $categoryId;
		$cat        = $service->findById($categoryId, $clang);

		if ($cat) return $cat;

		switch ($default) {
			case self::CURRENT_ARTICLE:  $id = sly_Core::getCurrentArticleId();   break;
			case self::START_ARTICLE:    $id = sly_Core::getSiteStartArticleId(); break;
			case self::NOTFOUND_ARTICLE: $id = sly_Core::getNotFoundArticleId();  break;
			// no default case by design
		}

		if (isset($id)) {
			$cat = $service->findById($id, $clang);
			if ($cat) return $cat;
			throw new sly_Exception('Could not find a matching category, giving up.');
		}

		return $default;
	}

	/**
	 * @param  int     $parentId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array
	 */
	public static function findByParentId($parentId, $ignore_offlines = false, $clang = null) {
		return sly_Service_Factory::getCategoryService()->findByParentId($parentId, $ignore_offlines, $clang);
	}

	/**
	 * @param  boolean $ignore_offlines
	 * @param  int $clang
	 * @return array
	 */
	public static function getRootCategories($ignore_offlines = false, $clang = null) {
		return self::findByParentId(0, $ignore_offlines, $clang);
	}

	public static function canReadCategory(sly_Model_User $user, $categoryId) {
		if($user->isAdmin()) return true;
		static $canReadCache;

		if (!isset($canReadCache[$user->getId()])) {
			$canReadCache[$user->getId()] = array();
		}

		if (!isset($canReadCache[$user->getId()][$categoryId])) {
			$canReadCache[$user->getId()][$categoryId] = false;

			if(sly_Util_Article::canEditContent($user, $categoryId)) {
				$canReadCache[$user->getId()][$categoryId] = true;
			} else {

				//check all children for write rights
				$article = self::findById($categoryId);
				if ($article) {
					$path = $article->getPath().$article->getId().'|%';
				} else {
					$path = '|%';
				}
				$query  = sly_DB_Persistence::getInstance();
				$prefix = sly_Core::getTablePrefix();
				$query->query('SELECT DISTINCT id FROM '.$prefix.'article WHERE path LIKE ?', array($path));
				foreach($query as $row) {
					if(sly_Util_Article::canEditContent($user, $row['id'])) {
						$canReadCache[$user->getId()][$categoryId] = true;
						break;
					}
				}
			}
		}
		return $canReadCache[$categoryId];
	}

}
