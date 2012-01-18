<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class sly_Slice_Values {
	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	public function value($id, $default = null) {
		if(!array_key_exists($id, $this->data)) return $default;
		return $this->data[$id];
	}
}
