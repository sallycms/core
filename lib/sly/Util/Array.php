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
 * @ingroup util
 */
class sly_Util_Array {
	private $array = array(); ///< array

	/**
	 * @param array $data
	 */
	public function __construct($data = array()) {
		$this->array = $data;
	}

	/**
	 * @throws sly_Exception
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function set($key, $value) {
		$key = trim($key, '/');

		if (strlen($key) == 0) {
			throw new sly_Exception('Key must not be empty!');
		}

		if (strpos($key, '/') === false) {
			$this->array[$key] = $value;
			return $value;
		}

		// Da wir Schreibvorgänge anstoßen werden, arbeiten wir hier explizit
		// mit Referenzen. Ja, Referenzen sind i.d.R. böse, deshalb werden sie auch
		// in get() und has() nicht benutzt. Copy-on-Write und so.

		$path = self::getPath($key);
		$res  = &$this->array;

		foreach ($path as $step) {
			if (!is_array($res)) throw new sly_Exception('Cannot make an array out of a scalar value.');
			if (!array_key_exists($step, $res)) $res[$step] = array();
			$res = &$res[$step];
		}

		$res = $value;
		return $value;
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$key = trim($key, '/');

		if (empty($key)) return $this->array;

		if (strpos($key, '/') === false) {
			if (!array_key_exists($key, $this->array)) {
				return $default;
			}

			return $this->array[$key];
		}

		$path = self::getPath($key);
		$res  = $this->array;

		foreach ($path as $step) {
			if (!array_key_exists($step, $res)) {
				return $default;
			}

			$res = $res[$step];
		}

		return $res;
	}

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function has($key) {
		$key = trim($key, '/');

		if (empty($key)) return true;
		if (strpos($key, '/') === false) return array_key_exists($key, $this->array);

		$path = self::getPath($key);
		$curr = $this->array;
		$res  = true;

		foreach ($path as $step) {
			if (!is_array($curr) || !array_key_exists($step, $curr)) {
				$res = false;
				break;
			}

			$curr = $curr[$step];
		}

		return $res;
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return array
	 */
	public function hasget($key, $default = null) {
		$key = trim($key, '/');

		if (empty($key)) return array(true, $this->array);
		if (array_key_exists($key, $this->array)) array(true, $this->array[$key]);

		$path = self::getPath($key);
		$res  = $this->array;

		foreach ($path as $step) {
			if (!array_key_exists($step, $res)) {
				return array(false, $default);
			}

			$res = $res[$step];
		}

		return array(true, $res);
	}

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function remove($key) {
		$key = trim($key, '/');

		if (empty($key)) {
			$this->array = array();
			return true;
		}

		if (strpos($key, '/') === false) {
			unset($this->array[$key]);
			return true;
		}

		$path = self::getPath($key);
		$last = array_pop($path);
		$curr = &$this->array;

		foreach ($path as $step) {
			if (!array_key_exists($step, $curr)) return false;
			$curr = &$curr[$step];
		}

		unset($curr[$last]);
		return true;
	}

	/**
	 * @param  array $array
	 * @return boolean
	 */
	public function hasMergeCollision($array) {
		return $this->hasMergeCollisionRecursive($this->array, $array);
	}

	/**
	 * @param  array $array
	 * @param  array $array1
	 * @return boolean
	 */
	private function hasMergeCollisionRecursive($array, $array1) {
		foreach ($array1 as $key => $value) {
			if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
				if (!$this->hasMergeCollisionRecursive($array[$key], $value)) return false;
			}
			else {
				if ($array[$key] != $value) return false;
			}
		}

		return true;
	}

	/**
	 * @param  string $key
	 * @return array
	 */
	protected static function getPath($key) {
		$parts   = explode('/', $key);
		$changes = false;

		foreach ($parts as $idx => $part) {
			if ($part === '') {
				unset($parts[$idx]);
				$changes = true;
			}
		}

		return $changes ? array_values($parts) : $parts;
	}

	/**
	 * Checks, if an array is associative
	 *
	 * @param  array $array  the array to check
	 * @return boolean       true, if the array is associative
	 */
	public static function isAssoc($array) {
		return is_array($array) && (empty($array) || 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
	}

	/**
	 * Returns true, when the predicate matches at least one element of the array.
	 *
	 * @param  callback $predicate  The predicate (callback function)
	 * @param  array    $array      Array to search in
	 * @return boolean              true, when the predicate matches at least once
	 */
	public static function any($predicate, $array) {
		foreach ($array as $element) if ($predicate($element)) return true;
		return false;
	}

	/**
	 * Returns true, when the predicate matches ALL elements of the array.
	 *
	 * @param  callback $predicate  The predicate (callback function)
	 * @param  array    $array      Array to search in
	 * @return boolean              true, when the predicate matches all
	 */
	public static function all($predicate, $array) {
		foreach ($array as $element) if (!$predicate($element)) return false;
		return true;
	}

	/**
	 * Returns true, when the predicate matches at least one key of the array.
	 *
	 * @param  callback $predicate  The predicate (callback function)
	 * @param  array    $array      Array to search in
	 * @return boolean              true, when the predicate matches at least one key
	 */
	public static function anyKey($predicate, $array) {
		return self::any($predicate, array_keys($array));
	}

	/**
	 * Checks, if an array is multidimensional
	 *
	 * @param  array $array  The array to check
	 * @return boolean       true, when the array is multidimensional
	 */
	public static function isMultiDim($array) {
		return self::any('is_array', $array);
	}

	/**
	 * Denests the nested arrays within the given array
	 *
	 * @see    http://snippets.dzone.com/posts/show/4660
	 *
	 * @param  array $array  The array to flatten
	 * @return array
	 */
	public static function flatten(array $array) {
		$i = 0;
		$n = count($array);

		while ($i < $n) {
			if (is_array($array[$i])) {
				array_splice($array, $i, 1, $array[$i]);
			}
			else {
				++$i;
			}
		}

		return $array;
	}
}
