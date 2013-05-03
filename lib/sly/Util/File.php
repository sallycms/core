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
class sly_Util_File {
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
		$extension = self::getExtension($filename);
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
			$extension = $extension === null ? self::getExtension($filename) : $extension;
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

		$extension = self::getExtension($filename);

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
	 * @return string
	 */
	public static function getExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
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
			/*
			Using the new, fancy finfo extension can lead to serious problems on poorly-
			configured server (or Windows boxes). The extension will either just report
			false (which is fine, we could fallback to our list) or wrongly report data
			(e.g. 'text/plain' for .css files, in which cases falling back would not work).
			So to avoid this headache, we always use the prebuilt list of mimetypes and
			all is well.

			if (!file_exists($filename)) {
				throw new sly_Exception('Cannot get mimetype of non-existing file '.$filename.'.');
			}

			$type = null;

			// try the new, recommended way
			if (function_exists('finfo_file')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$type  = finfo_file($finfo, $filename);
			}

			// argh, let's see if this old one exists
			elseif (function_exists('mime_content_type')) {
				$type = mime_content_type($filename);
			}
			*/

			$ext      = mb_strtolower(self::getExtension($realName === null ? $filename : $realName));
			$types    = sly_Util_YAML::load(SLY_COREFOLDER.'/config/mimetypes.yml');
			$mimetype = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
		}

		return $mimetype;
	}
}
