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

	/**
	 * Checks if a constraint matches a specific version
	 *
	 * @param  string  $constraints the constraints, like '>2.0,<3.0'
	 * @param  string  $version     the version to match against, null for the Sally version
	 * @return boolean              true if it matches, else false
	 */
	public static function isCompatible($constraints, $version = null) {
		$version = $version === null ? sly_Core::getVersion('X.Y.Z') : $version;
		$parser  = new sly_Service_VersionParser();
		$checks  = $parser->parseConstraints($constraints);
		$version = $parser->normalize($version);
		$result  = true;

		foreach ($checks as $check) {
			list ($op, $ver) = $check;

			if ($op === '=') {
				$op = '==';
			}

			$result &= version_compare($version, $ver, $op);
		}

		return !!$result;
	}

	/**
	 * @param  string $component
	 */
	public static function remove($component) {
		sly_Core::config()->remove('versions/'.$component);
	}
}
