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
 * @author Christoph
 */
class sly_Util_MediaCategory {
	/**
	 * checks whether a category exists or not
	 *
	 * @param  int $categoryID
	 * @return boolean
	 */
	public static function exists($categoryID) {
		return self::isValid(self::findById($categoryID));
	}

	/**
	 * @param  mixed $category
	 * @return boolean
	 */
	public static function isValid($category) {
		return is_object($category) && ($category instanceof sly_Model_MediaCategory);
	}

	/**
	 * @param  int $categoryID
	 * @return sly_Model_MediaCategory
	 */
	public static function findById($categoryID) {
		return sly_Core::getContainer()->getMediaCategoryService()->findById($categoryID);
	}

	/**
	 * @param  int $name
	 * @return array
	 */
	public static function findByName($name) {
		return sly_Core::getContainer()->getMediaCategoryService()->findByName($name);
	}

	/**
	 * @param  int $parentID
	 * @return array
	 */
	public static function findByParentId($parentID) {
		return sly_Core::getContainer()->getMediaCategoryService()->findByParentId($parentID);
	}

	/**
	 * @return array
	 */
	public static function getRootCategories() {
		return self::findByParentId(0);
	}
}
