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
 * @ingroup registry
 */
interface sly_Registry_Registry {
	/**
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function set($key, $value);

	/**
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function has($key);

	/**
	 * @param  string $key
	 * @return boolean
	 */
	public function remove($key);
}
