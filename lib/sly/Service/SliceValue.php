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
 * DB Model Klasse für Slice Values
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_SliceValue extends sly_Service_Model_Base_Id {
	protected $tablename = 'slice_value'; ///< string

	/**
	 * @param  array $params
	 * @return sly_Model_SliceValue
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_SliceValue($params);
	}

	/**
	 * @param  int    $slice_id
	 * @param  string $type
	 * @param  string $finder
	 * @return sly_Model_SliceValue
	 */
	public function findBySliceTypeFinder($slice_id, $type, $finder) {
		$where = array('slice_id' => $slice_id, 'type' => $type, 'finder' => $finder);
		$res   = $this->find($where);

		if (count($res) == 1) return $res[0];
		return null;
	}
}
