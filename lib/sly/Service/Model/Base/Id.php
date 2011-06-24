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
 * Description of Id
 *
 * @author zozi
 */
abstract class sly_Service_Model_Base_Id extends sly_Service_Model_Base {

	public function findById($id) {
		return $this->findOne(array('id' => (int) $id));
	}

	public function save(sly_Model_Base $model) {
		$persistence = sly_DB_Persistence::getInstance();

		if ($model->getId() == sly_Model_Base_Id::NEW_ID) {
			$data = $model->toHash();
			$persistence->insert($this->getTableName(), $data);
			$model->setId($persistence->lastId());
		}
		else {
			$persistence->update($this->getTableName(), $model->toHash(), $model->getPKHash());
		}

		return $model;
	}

	public function create($params) {
		if (isset($params['id'])) unset($params['id']);
		$model = $this->makeInstance($params);
		return $this->save($model);
	}
}
