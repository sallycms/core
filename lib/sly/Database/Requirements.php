<?php
/*
 * Copyright (c) 2016, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace sly\Database;

use PDO;
use sly_Util_Requirements;

class Requirements {
	protected static $drivers = array('mysql', 'oci', 'pgsql', 'sqlite'); ///< array
	protected static $versionConstraints = array(
		'mysql' => array(
			sly_Util_Requirements::OK      => '5.1',
			sly_Util_Requirements::WARNING => '5.0'
		),
		'sqlite' => array(
			sly_Util_Requirements::OK      => '3.0',
			sly_Util_Requirements::WARNING => '2.0'
		),
		'oci' => array(
			sly_Util_Requirements::OK      => '1.0', // ???
			sly_Util_Requirements::WARNING => '1.0'  // ???
		),
		'pgsql' => array(
			sly_Util_Requirements::OK      => '8.0',
			sly_Util_Requirements::WARNING => '7.1'
		)
	); ///< array

	/**
	 * @return array
	 */
	public static function getAvailableDrivers() {
		return array_intersect(self::$drivers, PDO::getAvailableDrivers());
	}

	public static function getDriverVersionConstraints($driver) {
		return isset(self::$versionConstraints[$driver]) ? self::$versionConstraints[$driver] : null;
	}

}

