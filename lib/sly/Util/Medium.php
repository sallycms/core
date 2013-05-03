<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\Filesystem;

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
	 * Remove unwanted characters from a filename
	 *
	 * @param  string                $filename
	 * @param  sly_Event_IDispatcher $dispatcher  if given, the SLY_MEDIUM_FILENAME will be fired
	 * @return string
	 */
	public static function cleanFilename($filename, sly_Event_IDispatcher $dispatcher = null) {
		$origFilename = $filename;
		$filename     = mb_strtolower($filename);
		$filename     = str_replace(array('ä', 'ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $filename);

		if ($dispatcher) {
			$filename = $dispatcher->filter('SLY_MEDIUM_FILENAME', $filename, array('orig' => $origFilename));
		}

		$filename = preg_replace('#[^a-z0-9.+-]#i', '_', $filename);
		$filename = trim(preg_replace('#_+#i', '_', $filename), '_');

		return $filename;
	}

	/**
	 * Append .txt to a file if its extension is blocked
	 *
	 * @param  string $filename
	 * @param  array  $blacklist  ['.php', '.exe', ...]
	 * @return string
	 */
	public static function sanitiseFileExtension($filename, array $blacklist) {
		$extension = sly_Util_String::getFileExtension($filename);
		if ($extension === '') return $filename;

		$filename  = mb_substr($filename, 0, -(mb_strlen($extension)+1));
		$extension = '.'.mb_strtolower($extension);
		$blacklist = array_map('mb_strtolower', $blacklist);

		if (in_array($extension, $blacklist)) {
			return $filename.$extension.'.txt';
		}

		return $filename.$extension;
	}

	/**
	 * Iterate a filename until a non-existing one was found
	 *
	 * This method will append '_1', '_2' etc. to a filename and hence test
	 * 'file.ext', 'file_1.ext', 'file_2.ext' until a free filename was found.
	 *
	 * Use the $extension parameter if you have a custom extension (which is
	 * not simply the part after the last dot). The $extension should include
	 * the separating dot (e.g. '.foo.bar').
	 *
	 * @param  string     $filename
	 * @param  Filesystem $fs         the filesystem to base the subindexing on, by default the media filesystem
	 * @param  string     $extension  use null to determine it automatically
	 * @return string
	 */
	public static function iterateFilename($filename, Filesystem $fs = null, $extension = null) {
		$fs = $fs ?: $container->get('sly-filesystem-media');

		if ($fs->has($filename)) {
			$extension = $extension === null ? sly_Util_String::getFileExtension($filename) : $extension;
			$basename  = substr($filename, 0, -(strlen($extension)+1));

			// this loop is empty on purpose
			for ($cnt = 1; $fs->has($basename.'_'.$cnt.$extension); ++$cnt);
			$filename = $basename.'_'.$cnt.$extension;
		}

		return $filename;
	}

	/**
	 * @param  string     $filename
	 * @param  boolean    $doSubindexing
	 * @param  boolean    $applyBlacklist
	 * @param  Filesystem $fs              the filesystem to base the subindexing on, by default the media filesystem
	 * @return string
	 */
	public static function createFilename($filename, $doSubindexing = true, $applyBlacklist = true, Filesystem $fs = null) {
		$origFilename = $filename;
		$container    = sly_Core::getContainer();
		$filename     = self::cleanFilename($filename, $container->getDispatcher()); // möp.png -> moep.png

		if (strlen($filename) === 0) {
			$filename = 'unnamed';
		}

		$extension = sly_Util_String::getFileExtension($filename);

		// check for disallowed extensions

		if ($applyBlacklist) {
			$blocked    = $container->getConfig()->get('blocked_extensions');
			$filename   = self::sanitiseFileExtension($filename, $blocked); // foo.php -> foo.php.txt
			$extension .= '.txt';
		}

		// increment filename suffix until an unique one was found

		if ($doSubindexing || $origFilename !== $filename) {
			$filename = self::iterateFilename($filename, $fs, $extension); // foo.png -> foo_4.png  /  foo.php.txt -> foo_4.php.txt
		}

		return $filename;
	}

	/**
	 * @param  string $filename
	 * @param  string $realName  optional; in case $filename is encoded and has no proper extension
	 * @return string
	 */
	public static function getMimetype($filename, $realName = null) {
		$size = @getimagesize($filename);

		// if it's an image, we know the type
		if (isset($size['mime'])) {
			$mimetype = $size['mime'];
		}

		// fallback to a generic type
		else {
			$mimetype = sly_Util_Mime::getType($realName === null ? $filename : $realName);
		}

		return $mimetype;
	}
}
