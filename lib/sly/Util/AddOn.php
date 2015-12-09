<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_AddOn {
	/**
	 * get addOn service
	 *
	 * @return sly_Service_AddOn
	 */
	public static function getService() {
		return sly_Core::getContainer()->getAddOnService();
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
	 * returns the base URI for all addOn assets
	 *
	 * @param  string $addon  addOn name
	 * @return string         a string like '../data/dyn/public/vendor/addon/'
	 */
	public static function assetBaseUri($addon) {
		$base = 'data/dyn/public/'.$addon.'/';
		return sly_Core::isBackend() ? "../$base" : $base;
	}

	/**
	 * Get the filesystem containing an addOn's dynamic files
	 *
	 * @param  string $addon  addon name
	 * @return Filesystem
	 */
	public static function dynFilesystem($addon) {
		return self::getService()->getDynFilesystem($addon);
	}

	/**
	 * Get the full path to an addOn's temp directory
	 *
	 * @param  string $addon  addon name
	 * @return Filesystem
	 */
	public static function tempDirectory($addon) {
		return self::getService()->getTempDirectory($addon);
	}

	/**
	 * returns the addon's version
	 *
	 * @param  string $addon  addOn name
	 * @return string
	 */
	public static function getVersion($addon) {
		return self::getService()->getPackageService()->getVersion($addon);
	}

	/**
	 * returns the addon's author
	 *
	 * @param  string $addon  addOn name
	 * @return string
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
