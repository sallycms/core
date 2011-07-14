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
class sly_Util_Medium {
	/**
	 * checks wheter a medium exists or not
	 *
	 * @param  int $mediumId
	 * @return boolean
	 */
	public static function exists($mediumId) {
		return self::isValid(self::findById($mediumId));
	}

	/**
	 *
	 * @param  mixed $medium
	 * @return boolean
	 */
	public static function isValid($medium) {
		return is_object($medium) && ($medium instanceof sly_Model_Medium);
	}

	/**
	 *
	 * @param  int $mediumId
	 * @return sly_Model_Medium
	 */
	public static function findById($mediumId) {
		return sly_Service_Factory::getMediumService()->findById($mediumId);
	}

	/**
	 *
	 * @param  string $filename
	 * @return sly_Model_Medium
	 */
	public static function findByFilename($filename) {
		return sly_Service_Factory::getMediumService()->findByFilename($filename);
	}

	/**
	 *
	 * @param  int $categoryId
	 * @return array
	 */
	public static function findByCategory($categoryId) {
		return sly_Service_Factory::getMediumService()->findMediaByCategory($categoryId);
	}

	/**
	 *
	 * @param  string $extension
	 * @return array
	 */
	public static function findByExtension($extension) {
		return sly_Service_Factory::getMediumService()->findMediaByExtension($extension);
	}
}
