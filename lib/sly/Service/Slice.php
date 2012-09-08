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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_Slice extends sly_Service_Model_Base_Id {
	protected $tablename  = 'slice'; ///< string

	/**
	 * @param  array $params
	 * @return sly_Model_Slice
	 */
	protected function makeInstance(array $params) {
		if (isset($params['serialized_values'])) {
			$params['values'] = json_decode($params['serialized_values'], true);
			if ($params['values'] === null) $params['values'] = array();
			unset($params['serialized_values']);
		}

		return new sly_Model_Slice($params);
	}

	/**
	 * @param  sly_Model_Slice $model
	 * @return sly_Model_Slice
	 */
	public function save(sly_Model_Base $model) {
		$persistence = $this->getPersistence();
		$data        = $model->toHash();

		$data['serialized_values'] = json_encode($data['values']);
		unset($data['values']);

		if ($model->getId() == sly_Model_Base_Id::NEW_ID) {
			$persistence->insert($this->getTableName(), $data);
			$model->setId($persistence->lastId());
		}
		else {
			$persistence->update($this->getTableName(), $data, $model->getPKHash());
		}

		return $model;
	}


	/**
	 * Kopiert einen Slice und seine Values
	 *
	 * @param  sly_Model_Slice $slice
	 * @return sly_Model_Slice
	 */
	public function copy(sly_Model_Slice $slice) {
		$new = new sly_Model_Slice($slice->toHash());
		return $this->save($new);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Slice $slice
	 * @return int
	 */
	public function deleteBySlice(sly_Model_Slice $slice) {
		return $this->deleteById($slice->getId());
	}
}
