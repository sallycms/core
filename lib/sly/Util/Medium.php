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
	const ERR_TYPE_MISMATCH    = 1; ///< int
	const ERR_INVALID_FILEDATA = 2; ///< int
	const ERR_UPLOAD_FAILED    = 3; ///< int

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
	 * @param  mixed $medium
	 * @return boolean
	 */
	public static function isValid($medium) {
		return is_object($medium) && ($medium instanceof sly_Model_Medium);
	}

	/**
	 * @param  int $mediumId
	 * @return sly_Model_Medium
	 */
	public static function findById($mediumId) {
		return sly_Service_Factory::getMediumService()->findById($mediumId);
	}

	/**
	 * @param  string $filename
	 * @return sly_Model_Medium
	 */
	public static function findByFilename($filename) {
		return sly_Service_Factory::getMediumService()->findByFilename($filename);
	}

	/**
	 * @param  int $categoryId
	 * @return array
	 */
	public static function findByCategory($categoryId) {
		return sly_Service_Factory::getMediumService()->findMediaByCategory($categoryId);
	}

	/**
	 * @param  string $extension
	 * @return array
	 */
	public static function findByExtension($extension) {
		return sly_Service_Factory::getMediumService()->findMediaByExtension($extension);
	}

	/**
	 * @throws sly_Exception
	 * @param  array            $fileData
	 * @param  int              $categoryID
	 * @param  string           $title
	 * @param  sly_Model_Medium $mediumToReplace
	 * @return sly_Model_Medium
	 */
	public static function upload(array $fileData, $categoryID, $title, sly_Model_Medium $mediumToReplace = null) {
		// check file data

		if (!isset($fileData['tmp_name'])) {
			throw new sly_Exception('Invalid file data array given.', self::ERR_INVALID_FILEDATA);
		}

		// If we're going to replace a medium, check if the type of the new
		// file matches the old one.

		if ($mediumToReplace) {
			$newType = self::getMimetype($fileData['tmp_name']);
			$oldType = $mediumToReplace->getFiletype();

			if ($newType !== $oldType) {
				throw new sly_Exception('The types of the old and new file don\'t match.', self::ERR_TYPE_MISMATCH);
			}
		}

		// check category

		$categoryID = (int) $categoryID;

		if (!sly_Util_MediaCategory::exists($categoryID)) {
			$categoryID = $mediumToReplace ? $mediumToReplace->getCategoryId() : 0;
		}

		// create filenames

		$filename    = $fileData['name'];
		$newFilename = $mediumToReplace ? $mediumToReplace->getFilename() : self::createFilename($filename);
		$dstFile     = SLY_MEDIAFOLDER.'/'.$newFilename;
		$file        = null;

		// move uploaded file

		if (!@move_uploaded_file($fileData['tmp_name'], $dstFile)) {
			throw new sly_Exception('Error while moving the uploaded file.', self::ERR_UPLOAD_FAILED);
		}

		@chmod($dstFile, sly_Core::config()->get('FILEPERM'));

		// create and save our file

		$service = sly_Service_Factory::getMediumService();

		if ($mediumToReplace) {
			$mediumToReplace->setFiletype($newType);
			$mediumToReplace->setFilesize(filesize($dstFile));

			$size = @getimagesize($targetFile);

			if ($size) {
				$mediumToReplace->setWidth($size[0]);
				$mediumToReplace->setHeight($size[1]);
			}

			$file = $service->update($mediumToReplace);

			// re-validate asset cache
			$service = sly_Service_Factory::getAssetService();
			$service->validateCache();
		}
		else {
			$file = $service->add(basename($dstFile), $title, $categoryID, $fileData['type'], $filename);
		}

		return $file;
	}

	/**
	 * @param  string  $filename
	 * @param  boolean $doSubindexing
	 * @return string
	 */
	public static function createFilename($filename, $doSubindexing = true) {
		$filename  = strtolower(self::correctEncoding($filename));
		$filename  = str_replace(array('ä','ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $filename);
		$filename  = preg_replace('#[^a-z0-9.+-]#i', '_', $filename);
		$extension = sly_Util_String::getFileExtension($filename);

		if ($extension) {
			$filename  = substr($filename, 0, -(strlen($extension)+1));
			$extension = '.'.$extension;

			// check for disallowed extensions (broken by design...)

			$blocked = sly_Core::config()->get('BLOCKED_EXTENSIONS');

			if (in_array($extension, $blocked)) {
				$filename .= $extension;
				$extension = '.txt';
			}
		}

		$newFilename = $filename.$extension;

		if ($doSubindexing) {
			// increment filename suffix until an unique one was found

			if (file_exists(SLY_MEDIAFOLDER.'/'.$newFilename)) {
				for ($cnt = 1; file_exists(SLY_MEDIAFOLDER.'/'.$filename.'_'.$cnt.$extension); ++$cnt);
				$newFilename = $filename.'_'.$cnt.$extension;
			}
		}

		return $newFilename;
	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	public static function correctEncoding($filename) {
		$enc = mb_detect_encoding($filename, 'Windows-1252, ISO-8859-1, ISO-8859-2, UTF-8');
		if ($enc != 'UTF-8') $filename = mb_convert_encoding($filename, 'UTF-8', $enc);
		return $filename;
	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	public static function getMimetype($filename) {
		$size = @getimagesize($filename);

		// if it's an image, we know the type
		if (isset($size['mime'])) {
			$mimetype = $size['mime'];
		}

		// fallback to a generic type
		else {
			$mimetype = sly_Util_Mime::getType($filename);
		}

		return $mimetype;
	}
}
