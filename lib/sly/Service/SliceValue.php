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

	public function save(sly_Model_Base $model) {
		$model->setValue(json_encode($model->getValue()));
		return parent::save($model);
	}

	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$values = parent::find($where, $group, $order, $offset, $limit, $having);
		foreach($values as &$value) {
			$value->setValue(json_decode($value->getValue()));
		}
		return $values;
	}

	/**
	 * @param  int    $slice_id
	 * @param  string $type
	 * @param  string $finder
	 * @return sly_Model_SliceValue
	 */
	public function findBySliceFinder($slice_id, $finder) {
		$where = array('slice_id' => $slice_id, 'finder' => $finder);
		$res   = $this->find($where);

		if (count($res) == 1) return $res[0];
		return null;
	}
}
