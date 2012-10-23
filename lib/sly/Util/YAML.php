<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_YAML {
	/**
	 * get YAML service
	 *
	 * @return sly_Service_File_YAML
	 */
	protected static function getService() {
		return sly_Service_Factory::getService('File_YAML');
	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	public static function getCacheFile($filename) {
		return self::getService()->getCacheFile($filename);
	}

	/**
	 * @param  string $filename
	 * @return boolean
	 */
	public static function hasChanges($filename) {
		return self::getService()->hasChanges($filename);
	}

	/**
	 * @param  string $origfile
	 * @param  string $cachefile
	 * @return boolean
	 */
	public static function isCacheValid($origfile, $cachefile) {
		return self::getService()->isCacheValid($origfile, $cachefile);
	}

	/**
	 * Cached loading of a YAML file
	 *
	 * @throws sly_Exception
	 * @throws InvalidArgumentException
	 * @param  string  $filename             path to file to load
	 * @param  boolean $forceCached          always return cached version (if it exists)
	 * @param  boolean $disableRuntimeCache  do not cache the decoded file contents in $this->cache
	 * @return mixed                         parsed content
	 */
	public static function load($filename, $forceCached = false, $disableRuntimeCache = false) {
		return self::getService()->load($filename, $forceCached, $disableRuntimeCache);
	}

	/**
	 * @param string $filename
	 * @param mixed  $data
	 */
	public static function dump($filename, $data) {
		self::getService()->dump($filename, $data);
	}
}
