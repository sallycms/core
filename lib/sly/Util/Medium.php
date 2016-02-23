<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
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
	const ERR_TYPE_MISMATCH    = 1; ///< int
	const ERR_INVALID_FILEDATA = 2; ///< int
	const ERR_UPLOAD_FAILED    = 3; ///< int

	/**
	 * checks whether a medium exists or not
	 *
	 * @param  int $mediumID
	 * @return boolean
	 */
	public static function exists($mediumID) {
		return self::isValid(self::findById($mediumID));
	}

	/**
	 * @param  mixed $medium
	 * @return boolean
	 */
	public static function isValid($medium) {
		return is_object($medium) && ($medium instanceof sly_Model_Medium);
	}

	/**
	 * @param  int $mediumID
	 * @return sly_Model_Medium
	 */
	public static function findById($mediumID) {
		return sly_Core::getContainer()->getMediumService()->findById($mediumID);
	}

	/**
	 * @param  string $filename
	 * @return sly_Model_Medium
	 */
	public static function findByFilename($filename) {
		return sly_Core::getContainer()->getMediumService()->findByFilename($filename);
	}

	/**
	 * @param  int $categoryID
	 * @return array
	 */
	public static function findByCategory($categoryID) {
		return sly_Core::getContainer()->getMediumService()->findMediaByCategory($categoryID);
	}

	/**
	 * @param  string $extension
	 * @return array
	 */
	public static function findByExtension($extension) {
		return sly_Core::getContainer()->getMediumService()->findMediaByExtension($extension);
	}

	/**
	 * @deprecated  since 0.9, use sly_Util_File::createFilename() instead
	 *
	 * @param  string  $filename
	 * @param  boolean $doSubindexing
	 * @return string
	 */
	public static function createFilename($filename, $doSubindexing = true) {
		return sly_Util_File::createFilename($filename, $doSubindexing, true, null);
	}

	/**
	 * @deprecated  since 0.9, use sly_Util_File::getMimetype() instead
	 *
	 * @param  string $filename
	 * @param  string $realName  optional; in case $filename is encoded and has no proper extension
	 * @return string
	 */
	public static function getMimetype($filename, $realName = null) {
		return sly_Util_File::getMimetype($filename, $realName);
	}
}
