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
class sly_Util_Versions {
	/**
	 * @param  string $path
	 * @return string
	 */
	public static function get($path) {
		return sly_Core::config()->get('versions/'.$path, false);
	}

	/**
	 * @param  string $path
	 * @param  string $version
	 * @return string
	 */
	public static function set($path, $version) {
		return sly_Core::config()->set('versions/'.$path, $version);
	}
}
