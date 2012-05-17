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
class sly_Util_Composer extends sly_Util_JSON {
	const EXTRA_SUBKEY = 'sallycms';

	/**
	 * @param  string  $filename
	 * @param  string  $key
	 * @param  boolean $tryExtra  if true $key is first searched in /extra/sallycms/$key, before /$key ist searched
	 * @return string
	 */
	public static function getKey($filename, $key, $tryExtra = true) {
		if ($tryExtra) {
			$val = self::getSallyKey($filename, $key);
			if ($val !== null) return $val;
		}

		$data = self::load($filename);
		return array_key_exists($key, $data) ? $data[$key] : null;
	}

	/**
	 * @param  string $filename
	 * @param  string $key
	 * @return string
	 */
	public static function getSallyKey($filename, $key = null) {
		$extra  = self::getKey($filename, 'extra', false);
		$subkey = self::EXTRA_SUBKEY;

		// nothing set
		if (!isset($extra[$subkey])) return null;

		$extra = $extra[$subkey];

		// return everything if requested
		if ($key === null) return $extra;

		return array_key_exists($key, $extra) ? $extra[$key] : null;
	}
}
