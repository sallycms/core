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
 * @author Christoph
 */
class sly_Util_MediaCategory {
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
	 *
	 * @param  mixed $category
	 * @return boolean
	 */
	public static function isValid($category) {
		return is_object($category) && ($category instanceof sly_Model_MediaCategory);
	}

	/**
	 *
	 * @param  int $categoryId
	 * @return sly_Model_MediaCategory
	 */
	public static function findById($categoryId) {
		return sly_Service_Factory::getMediaCategoryService()->findById($categoryId);
	}

	/**
	 *
	 * @param  int $name
	 * @return array
	 */
	public static function findByName($name) {
		return sly_Service_Factory::getMediaCategoryService()->findByName($name);
	}

	/**
	 *
	 * @param  int $parentId
	 * @return array
	 */
	public static function findByParentId($parentId) {
		return sly_Service_Factory::getMediaCategoryService()->findByParentId($parentId);
	}

	/**
	 *
	 * @return array
	 */
	public static function getRootCategories() {
		return self::findByParentId(0);
	}
}
