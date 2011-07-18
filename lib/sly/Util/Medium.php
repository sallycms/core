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

	public static function upload(array $fileData, $categoryID, $title) {
		// check file data

		if (!isset($fileData['tmp_name'])) {
			throw new sly_Exception('Invalid file data array given.');
		}

		// check category

		$categoryID = (int) $categoryID;

		if (!sly_Util_MediaCategory::exists($categoryID)) {
			$categoryID = 0;
		}

		$filename    = $fileData['name'];
		$newFilename = self::createFilename($filename);

		// create filenames

		$dstFile = SLY_MEDIAFOLDER.'/'.$newFilename;
		$file    = null;

		// move uploaded file

		if (!@move_uploaded_file($fileData['tmp_name'], $dstFile)) {
			throw new sly_Exception('Error while moving the uploaded file.');
		}

		@chmod($dstFile, sly_Core::config()->get('FILEPERM'));

		// create and save our file

		$service = sly_Service_Factory::getMediumService();
		$file    = $service->add(basename($dstFile), $title, $category, $fileData['type'], $filename);

		return $file;
	}

	public static function createFilename($filename, $doSubindexing = true) {
		$filename    = self::correctEncoding($filename);
		$newFilename = strtolower($filename);
		$newFilename = str_replace(array('ä','ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $newFilename);
		$newFilename = preg_replace('#[^a-z0-9.+-]#i', '_', $newFilename);
		$lastDotPos  = strrpos($newFilename, '.');
		$fileLength  = strlen($newFilename);

		// split up extension

		if ($lastDotPos !== false) {
			$newName = substr($newFilename, 0, $lastDotPos);
			$newExt  = substr($newFilename, $lastDotPos);
		}
		else {
			$newName = $newFilename;
			$newExt  = '';
		}

		// check for disallowed extensions (broken by design...)

		$blocked = sly_Core::config()->get('MEDIAPOOL/BLOCKED_EXTENSIONS');

		if (in_array($newExt, $blocked)) {
			$newName .= $newExt;
			$newExt   = '.txt';
		}

		$newFilename = $newName.$newExt;

		if ($doSubindexing) {
			// increment filename suffix until an unique one was found

			if (file_exists(SLY_MEDIAFOLDER.'/'.$newFilename)) {
				for ($cnt = 1; file_exists(SLY_MEDIAFOLDER.'/'.$newName.'_'.$cnt.$newExt); ++$cnt);
				$newFilename = $newName.'_'.$cnt.$newExt;
			}
		}

		return $newFilename;
	}

	public static function correctEncoding($filename) {
		$enc = mb_detect_encoding($filename, 'Windows-1252, ISO-8859-1, ISO-8859-2, UTF-8');
		if ($enc != 'UTF-8') $filename = mb_convert_encoding($filename, 'UTF-8', $enc);
		return $filename;
	}
}
