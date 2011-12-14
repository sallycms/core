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
class sly_Util_Category {
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
	 * @param  int $categoryId
	 * @param  int $clang
	 * @return sly_Model_Category
	 */
	public static function findById($categoryId, $clang = null) {
		return sly_Service_Factory::getCategoryService()->findById($categoryId, $clang);
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
		static $canReadCache;

		if (!isset($canReadCache[$categoryId])) {
			$canReadCache[$categoryId] = false;

			if(sly_Util_Article::canEditContent($user, $categoryId)) $canReadCache[$categoryId] = true;

			//check all children for write rights
			$article = self::findById($categoryId);
			if ($article) {
				$path = $article->getPath().$article->getId().'|%';
			} else {
				$path = '|%';
			}
			$query  = sly_DB_Persistence::getInstance();
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
			$query->query('SELECT DISTINCT id FROM '.$prefix.'article WHERE path LIKE ?', array($path));
			foreach($query as $row) {
				if(sly_Util_Article::canEditContent($user, (int) $row['id'])) $canReadCache[$categoryId] = true;
			}
		}
		return $canReadCache[$categoryId];
	}

}
