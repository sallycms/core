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
	 * @param  boolean $tryExtra  if true and $key is not found at the root level, $key is also search in 'extra/sallycms/$key'
	 * @return string
	 */
	public static function getKey($filename, $key, $tryExtra = true) {
		$data = self::load($filename);

		// match
		if (array_key_exists($key, $data)) return $data[$key];

		// give up
		if (!$tryExtra) return null;

		// try extra
		return self::getSallyKey($filename, $key);
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
