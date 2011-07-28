<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Backport of array_replace_recursive for PHP < 5.3.0
 *
 * Implementation used from http://de.php.net/manual/en/function.array-replace-recursive.php#92574
 *
 * @author: Gregor at der-meyer dot de
 */
if (!function_exists('array_replace_recursive')) {
	function array_replace_recursive($array, $array1) {
		if (!function_exists('array_replace_recursive___recurse')) {
			function array_replace_recursive___recurse($array, $array1) {
				foreach ($array1 as $key => $value) {
					// create new key in $array, if it is empty or not an array
					if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))) {
						$array[$key] = array();
					}

					// overwrite the value in the base array
					if (is_array($value)) {
						$value = array_replace_recursive___recurse($array[$key], $value);
					}
					$array[$key] = $value;
				}
				return $array;
			}
		}

		// handle the arguments, merge one by one
		$args = func_get_args();
		$array = $args[0];
		if (!is_array($array)) {
			return $array;
		}
		for ($i = 1; $i < count($args); $i++) {
			if (is_array($args[$i])) {
				$array = array_replace_recursive___recurse($array, $args[$i]);
			}
		}
		return $array;
	}
}

// Kompatibilität für Windows. fnmatch() ist erst ab PHP 5.3.0 verfügbar.
// Quelle: http://us3.php.net/manual/en/function.fnmatch.php#71725

if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.', '\[' => '[', '\]' => ']'))."$#i", $string) > 0;
	}
}

// add PHP userland JSON implementation

if (!function_exists('json_encode')) {
	function json_get_service() {
		static $service = null;
		if ($service === null) $service = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		return $service;
	}

	function json_encode($value) {
		return json_get_service()->encode($value);
	}

	function json_decode($data) {
		return json_get_service()->decode($data);
	}
}
