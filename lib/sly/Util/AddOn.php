<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_AddOn {
	public static function getService() {
		return sly_Service_Factory::getAddOnService();
	}

	/**
	 * checks if an addon is installed
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function isInstalled($addon) {
		return self::getService()->isInstalled($addon);
	}

	/**
	 * checks if an addon is available
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function isAvailable($addon) {
		return self::getService()->isAvailable($addon);
	}

	/**
	 * returns the full path to the public directory
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function publicDirectory($addon) {
		return self::getService()->publicDirectory($addon);
	}

	/**
	 * returns the full path to the internal directory
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function internalDirectory($addon) {
		return self::getService()->internalDirectory($addon);
	}

	/**
	 * returns the addon's version
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function getVersion($addon) {
		return self::getService()->getPackageService()->getVersion($addon);
	}

	/**
	 * returns the addon's author
	 *
	 * @param  string $addon  the addon to check
	 * @return boolean
	 */
	public static function getAuthor($addon) {
		return self::getService()->getPackageService()->getAuthor($addon);
	}

	/**
	 * sets a property
	 *
	 * @param  string $addon     the addon
	 * @param  string $property
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function setProperty($addon, $property, $value) {
		return self::getService()->setProperty($addon, $property, $value);
	}

	/**
	 * gets a property
	 *
	 * @param  string $addon     the addon
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function getProperty($addon, $property, $default = null) {
		return self::getService()->setProperty($addon, $property, $default);
	}
}
