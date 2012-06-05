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
	 * @author  Composer
	 * @license MIT
	 * @see     https://github.com/composer/composer/
	 *
	 * @param  string  $constraint the constraint, like '>2.0'
	 * @param  string  $version    the version to match against, null for the Sally version
	 * @return boolean             true if it matches, else false
	 */
	public static function isCompatible($constraint, $version = null) {
		$version = $version === null ? sly_Core::getVersion('X.Y.Z') : $version;

		if (preg_match('{^[x*](\.[x*])*$}i', $constraint)) {
			return true;
		}

		$checks = array();

		// match wildcard constraints
		if (preg_match('{^(\d+)(?:\.(\d+))?(?:\.(\d+))?\.[x*]$}', $constraint, $matches)) {
			// X.Y.Z[.*]
			if (isset($matches[3])) {
				$highVersion = $matches[1].'.'.$matches[2].'.'.$matches[3].'.9999999';

				if ($matches[3] === '0') {
					$lowVersion = $matches[1].'.'.($matches[2]-1).'.9999999.9999999';
				}
				else {
					$lowVersion = $matches[1].'.'.$matches[2].'.'.($matches[3]-1). '.9999999';
				}
			}

			// X.Y[.*]
			elseif (isset($matches[2])) {
				$highVersion = $matches[1].'.'.$matches[2].'.9999999.9999999';

				if ($matches[2] === '0') {
					$lowVersion = ($matches[1]-1).'.9999999.9999999.9999999';
				}
				else {
					$lowVersion = $matches[1].'.'.($matches[2]-1).'.9999999.9999999';
				}
			}

			// X[.*]
			else {
				$highVersion = $matches[1].'.9999999.9999999.9999999';

				if ($matches[1] === '0') {
					$checks[] = array('<', $highVersion);
				}
				else {
					$lowVersion = ($matches[1]-1).'.9999999.9999999.9999999';
				}
			}

			if (empty($checks)) {
				$checks = array(
					array('>', $lowVersion),
					array('<', $highVersion)
				);
			}
		}

		// match operators constraints
		elseif (preg_match('{^(>=?|<=?|==?)?\s*(.*)}', $constraint, $matches)) {
			$checks[] = array($matches[1] ? $matches[1] : '=', $matches[2]);
		}

		// boom
		else {
			throw new sly_Exception('Could not parse version constraint '.$constraint);
		}

		$result = true;

		foreach ($checks as $check) {
			list ($op, $ver) = $check;

			if ($op === '=') {
				$op = '==';
			}

			$result &= version_compare($version, $ver, $op);
		}

		return !!$result;
	}
}
