<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Array wrapper
 *
 * This class wraps a single array and allows access by key. The key can
 * optionally be normalized, which is the primary advantage over a native array
 * and is primarily used inside sly_Request.
 *
 * @ingroup util
 */
class sly_Util_ArrayObject implements Countable, IteratorAggregate, ArrayAccess {
	private $array;       ///< array
	private $normalizer;  ///< mixed

	const NORMALIZE_NONE        = 0;
	const NORMALIZE_HTTP_HEADER = 1;
	const NORMALIZE_LOWERCASE   = 2;
	const NORMALIZE_UPPERCASE   = 3;

	/**
	 * @param array $data
	 * @param mixed $normalizer  either a NORMALIZE_* constant or a callable
	 */
	public function __construct(array $data = array(), $normalizer = self::NORMALIZE_NONE) {
		$this->setNormalizer($normalizer);
		$this->setData($data);
	}

	/**
	 * @param mixed $normalizer  either a NORMALIZE_* constant or a callable
	 */
	public function setNormalizer($normalizer) {
		if (is_callable($normalizer)) {
			$this->normalizer = $normalizer;
		}
		elseif (in_array($normalizer, array(self::NORMALIZE_NONE, self::NORMALIZE_LOWERCASE, self::NORMALIZE_UPPERCASE, self::NORMALIZE_HTTP_HEADER))) {
			$this->normalizer = $normalizer;
		}
		else {
			throw new InvalidArgumentException('$normalizer must be either a callable or a valid NORMALIZE_* constant.');
		}
	}

	/**
	 * @throws InvalidArgumentException
	 * @param  array $data
	 * @return sly_Util_ArrayObject  return to self
	 */
	public function setData(array $data) {
		$this->array = array();

		foreach ($data as $key => $value) {
			$this->array[$this->normalize($key)] = $value;
		}

		return $this;
	}

	/**
	 * @throws InvalidArgumentException
	 * @param  string|int $key
	 * @param  mixed      $value
	 * @return sly_Util_ArrayObject  return to self
	 */
	public function set($key, $value) {
		$this->array[$this->normalize($key)] = $value;
		return $this;
	}

	/**
	 * @param  string|int $key      the key to find
	 * @param  string     $type     the new variable type or 'raw' of no casting should happen
	 * @param  string     $default  the default value if $key was not found
	 * @return mixed
	 */
	public function get($key, $type = 'raw', $default = null) {
		return sly_setarraytype($this->array, $this->normalize($key), $type, $default);
	}

	/**
	 * @param  string|int $key
	 * @return sly_Util_ArrayObject  return to self
	 */
	public function remove($key) {
		$key = $this->normalize($key);

		if (array_key_exists($key, $this->array)) {
			unset($this->array[$key]);
		}

		return $this;
	}

	/**
	 * @return array  all data from the instance
	 */
	public function all() {
		return $this->array;
	}

	/**
	 * @return array  all keys from the instance
	 */
	public function keys() {
		return array_keys($this->array);
	}

	/**
	 * @param  string|int $key
	 * @return boolean
	 */
	public function has($key) {
		return array_key_exists($this->normalize($key), $this->array);
	}

	/**
	 * @param  string|int $key
	 * @param  mixed      $value  value to compare to
	 * @return boolean
	 */
	public function contains($key, $value) {
		return $value === $this->get($key, 'raw', null);
	}

	/**
	 * Returns an iterator for headers
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->array);
	}

	/**
	 * Returns the number of elements
	 *
	 * @return int
	 */
	public function count() {
		return count($this->array);
	}

	/**
	 * @throws InvalidArgumentException
	 * @param  string|int $offset
	 * @param  mixed      $value
	 */
	public function offsetSet($offset, $value) {
		if ($offset === null) {
			$this->array[] = $value;
		}
		else {
			$this->array[$this->normalize($offset)] = $value;
		}
	}

	/**
	 * @param  string|int $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}

	/**
	 * @param string|int $offset
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}

	/**
	 * @param  string|int $offset
	 * @return mixed               value if found or null otherwise
	 */
	public function offsetGet($offset) {
		return $this->get($offset, 'raw', null);
	}

	/**
	 * @throws InvalidArgumentException
	 * @param  string|int $key
	 * @return string|int
	 */
	protected function normalize($key) {
		if (!is_string($key) && !is_int($key)) {
			throw new InvalidArgumentException('$key must be either a string or an int.');
		}

		switch ($this->normalizer) {
			case self::NORMALIZE_NONE:
				break;

			case self::NORMALIZE_LOWERCASE:
				$key = strtolower($key);
				break;

			case self::NORMALIZE_UPPERCASE:
				$key = strtoupper($key);
				break;

			case self::NORMALIZE_HTTP_HEADER:
				$key = strtr(strtolower($key), '_', '-');
				break;

			default:
				$key = call_user_func_array($this->normalizer, array($key));
				break;
		}

		if (!is_string($key) && !is_int($key)) {
			throw new InvalidArgumentException('Normalized key is neither a string nor an int.');
		}

		return $key;
	}
}
