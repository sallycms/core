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
	 * @throws sly_Exception
	 * @param  array            $fileData
	 * @param  int              $categoryID
	 * @param  string           $title
	 * @param  sly_Model_Medium $mediumToReplace
	 * @param  boolean          $allowFakeUploads      if true, there will be no check if the file is a real upload
	 * @return sly_Model_Medium
	 */
	public static function upload(array $fileData, $categoryID, $title, sly_Model_Medium $mediumToReplace = null, sly_Model_User $user = null, $allowFakeUpload = false) {
		// check file data

		if (!isset($fileData['tmp_name'])) {
			throw new sly_Exception(t('invalid_file_data'), self::ERR_INVALID_FILEDATA);
		}

		// If we're going to replace a medium, check if the type of the new
		// file matches the old one.

		if ($mediumToReplace) {
			$newType = self::getMimetype($fileData['tmp_name'], $fileData['name']);
			$oldType = $mediumToReplace->getFiletype();

			if ($newType !== $oldType) {
				throw new sly_Exception(t('types_of_old_and_new_do_not_match'), self::ERR_TYPE_MISMATCH);
			}
		}

		// check category

		$categoryID = (int) $categoryID;

		if (!sly_Util_MediaCategory::exists($categoryID)) {
			$categoryID = $mediumToReplace ? $mediumToReplace->getCategoryId() : 0;
		}

		// create filenames

		$filename = $fileData['name'];
		$dstFile  = $mediumToReplace ? $mediumToReplace->getFilename() : self::createFilename($filename);
		$file     = null;

		// move uploaded file
		try {
			if (!$allowFakeUpload && !is_uploaded_file($fileData['tmp_name'])) {
				throw new sly_Exception('This is not an uploaded file.', self::ERR_INVALID_FILEDATA);
			}
			sly_Core::getContainer()->getMediaFilesystem()->move($fileData['tmp_name'], $dstFile);
		} catch (sly_Filesystem_Exception $e) {
			throw new sly_Exception(t('error_moving_uploaded_file', basename($fileData['tmp_name'])).' '.$e->getMessage(), self::ERR_UPLOAD_FAILED);
		}

		@chmod($dstFile, sly_Core::config()->get('fileperm'));

		// create and save our file

		$service = sly_Core::getContainer()->getMediumService();

		if ($mediumToReplace) {
			$mediumToReplace->setFiletype($newType);
			$mediumToReplace->setFilesize(filesize($dstFile));

			$size = @getimagesize($dstFile);

			if ($size) {
				$mediumToReplace->setWidth($size[0]);
				$mediumToReplace->setHeight($size[1]);
			}
			else {
				$mediumToReplace->setWidth(0);
				$mediumToReplace->setHeight(0);
			}

			$file = $service->update($mediumToReplace, $user);

			// re-validate asset cache
			$service = sly_Core::getContainer()->getAssetService();
			$service->validateCache();
		}
		else {
			$file = $service->add(basename($dstFile), $title, $categoryID, $fileData['type'], $filename, $user);
		}

		return $file;
	}

	/**
	 * @param  string  $filename
	 * @param  boolean $doSubindexing
	 * @return string
	 */
	public static function createFilename($filename, $doSubindexing = true) {
		$origFilename = $filename;
		$filename     = mb_strtolower($filename);
		$filename     = str_replace(array('ä', 'ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $filename);
		$filename     = sly_Core::dispatcher()->filter('SLY_MEDIUM_FILENAME', $filename, array('orig' => $origFilename));
		$filename     = preg_replace('#[^a-z0-9.+-]#i', '_', $filename);
		$filename     = trim(preg_replace('#_+#i', '_', $filename), '_');
		$extension    = sly_Util_String::getFileExtension($filename);

		if (strlen($filename) === 0) {
			$filename = 'unnamed';
		}

		if ($extension) {
			$filename  = substr($filename, 0, -(strlen($extension)+1));
			$extension = '.'.$extension;

			// check for disallowed extensions (broken by design...)

			$blocked = sly_Core::config()->get('blocked_extensions');

			if (in_array($extension, $blocked)) {
				$filename .= $extension;
				$extension = '.txt';
			}
		}

		$newFilename = $filename.$extension;

		if ($doSubindexing || $origFilename !== $newFilename) {
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
	public static function getMimetype($filename, $realName) {
		$size = @getimagesize($filename);

		// if it's an image, we know the type
		if (isset($size['mime'])) {
			$mimetype = $size['mime'];
		}

		// fallback to a generic type
		else {
			$mimetype = sly_Util_Mime::getType($realName);
		}

		return $mimetype;
	}
}
