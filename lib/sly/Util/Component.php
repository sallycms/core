<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_Component {
	public static function getService() {
		return sly_Service_Factory::getComponentService();
	}

	/**
	 * checks if a component is installed
	 *
	 * @param  mixed $component  the component to check
	 * @return boolean
	 */
	public static function isInstalled($component) {
		return self::getService()->isInstalled($component);
	}

	/**
	 * checks if a component is available
	 *
	 * @param  mixed $component  the component to check
	 * @return boolean
	 */
	public static function isAvailable($component) {
		return self::getService()->isAvailable($component);
	}

	/**
	 * returns the full path to the public directory
	 *
	 * @param  mixed $component  the component to check
	 * @return boolean
	 */
	public static function publicFolder($component) {
		return self::getService()->publicFolder($component);
	}

	/**
	 * returns the full path to the internal directory
	 *
	 * @param  mixed $component  the component to check
	 * @return boolean
	 */
	public static function internalFolder($component) {
		return self::getService()->internalFolder($component);
	}

	/**
	 * returns the component's version
	 *
	 * @param  mixed $component  the component to check
	 * @return boolean
	 */
	public static function getVersion($component) {
		return self::getService()->getVersion($component);
	}

	/**
	 * sets a property
	 *
	 * @param  mixed  $component  the component to check
	 * @param  string $property
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function setProperty($component, $property, $value) {
		return self::getService()->setProperty($component, $property, $value);
	}

	/**
	 * gets a value
	 *
	 * @param  mixed  $component  the component to check
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function getProperty($component, $property, $default = null) {
		return self::getService()->setProperty($component, $property, $default);
	}
}
