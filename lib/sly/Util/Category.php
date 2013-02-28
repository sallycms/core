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
class sly_Util_Category {
	const CURRENT_ARTICLE  = -1; ///< int
	const START_ARTICLE    = -2; ///< int
	const NOTFOUND_ARTICLE = -3; ///< int

	/**
	 * checks whether a category exists or not
	 *
	 * @param  int $categoryID
	 * @param  int $clang
	 * @return boolean
	 */
	public static function exists($categoryID, $clang = null) {
		return self::isValid(self::findById($categoryID, $clang));
	}

	/**
	 * @param  mixed $category
	 * @return boolean
	 */
	public static function isValid($category) {
		return is_object($category) && ($category instanceof sly_Model_Category);
	}

	/**
	 * @throws sly_Exception
	 * @param  int   $categoryID
	 * @param  int   $clang
	 * @param  mixed $default
	 * @return sly_Model_Category
	 */
	public static function findById($categoryID, $clang = null, $default = null) {
		$service    = sly_Core::getContainer()->getCategoryService();
		$categoryID = (int) $categoryID;
		$cat        = $service->findById($categoryID, $clang);

		if ($cat) return $cat;

		switch ($default) {
			case self::CURRENT_ARTICLE:  $id = sly_Core::getCurrentArticleId();   break;
			case self::START_ARTICLE:    $id = sly_Core::getSiteStartArticleId(); break;
			case self::NOTFOUND_ARTICLE: $id = sly_Core::getNotFoundArticleId();  break;
			// no default case by design
		}

		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		if (isset($id)) {
			$cat = $service->findById($id, $clang);
			if ($cat) return $cat;
			throw new sly_Exception('Could not find a matching category, giving up.');
		}

		return $default;
	}

	/**
	 * @param  int     $parentID
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public static function findByParentId($parentID, $ignoreOfflines = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}
		return sly_Core::getContainer()->getCategoryService()->findByParentId($parentID, $clang, $ignoreOfflines);
	}

	/**
	 * @param  boolean $ignoreOfflines
	 * @param  int     $clang
	 * @return array
	 */
	public static function getRootCategories($ignoreOfflines = false, $clang = null) {
		if ($clang === false || $clang === null) {
			$clang = sly_Core::getCurrentClang();
		}
		return self::findByParentId(0, $clang, $ignoreOfflines);
	}
}
