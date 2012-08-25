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
 * Description of Id
 *
 * @author zozi
 */
abstract class sly_Service_Model_Base_Id extends sly_Service_Model_Base {
	/**
	 * @param  int $id
	 * @return sly_Model_Base
	 */
	public function findById($id) {
		return $this->findOne(array('id' => (int) $id));
	}

	/**
	 * @param  sly_Model_Base $model
	 * @return sly_Model_Base
	 */
	public function save(sly_Model_Base $model) {
		$persistence = sly_DB_Persistence::getInstance();
		$data        = $model->toHash();

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
	 * @param  array $params
	 * @return sly_Model_Base
	 */
	public function create($params) {
		if (isset($params['id'])) unset($params['id']);
		$model = $this->makeInstance($params);
		return $this->save($model);
	}

	/**
	 * @param  int $id
	 * @return int
	 */
	public function deleteById($id) {
		return $this->delete(array('id' => (int) $id));
	}
}
