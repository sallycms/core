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
 * @since  0.6
 * @author zozi@webvariants.de
 */
class sly_Slice_Values {
	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	public function get($id, $default = null) {
		if (!array_key_exists($id, $this->data)) return $default;
		return $this->data[$id];
	}
}
