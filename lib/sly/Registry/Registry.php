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
 * @ingroup registry
 */
interface sly_Registry_Registry {
	public function set($key, $value);
	public function get($key);
	public function has($key);
	public function remove($key);
}
