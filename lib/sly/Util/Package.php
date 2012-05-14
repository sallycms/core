<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_Package {
	public static function getService() {
		return sly_Service_Factory::getPackageService();
	}

	/**
	 * checks if a package is installed
	 *
	 * @param  string $package  the package to check
	 * @return boolean
	 */
	public static function isInstalled($package) {
		return self::getService()->isInstalled($package);
	}

	/**
	 * checks if a package is available
	 *
	 * @param  string $package  the package to check
	 * @return boolean
	 */
	public static function isAvailable($package) {
		return self::getService()->isAvailable($package);
	}

	/**
	 * returns the full path to the public directory
	 *
	 * @param  string $package  the package to check
	 * @return boolean
	 */
	public static function publicDirectory($package) {
		return self::getService()->publicDirectory($package);
	}

	/**
	 * returns the full path to the internal directory
	 *
	 * @param  string $package  the package to check
	 * @return boolean
	 */
	public static function internalDirectory($package) {
		return self::getService()->internalDirectory($package);
	}

	/**
	 * returns the package's version
	 *
	 * @param  string $package  the package to check
	 * @return boolean
	 */
	public static function getVersion($package) {
		return self::getService()->getVersion($package);
	}

	/**
	 * sets a property
	 *
	 * @param  string $package  the package
	 * @param  string $property
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function setProperty($package, $property, $value) {
		return self::getService()->setProperty($package, $property, $value);
	}

	/**
	 * gets a property
	 *
	 * @param  string $package  the package
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function getProperty($package, $property, $default = null) {
		return self::getService()->setProperty($package, $property, $default);
	}
}
