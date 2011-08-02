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
 * @ingroup service
 */
abstract class sly_Service_Model_Base {
	protected $tablename;
	protected $hasCascade = false;

	protected abstract function makeInstance(array $params);

	protected function getTableName() {
		return $this->tablename;
	}

	public function findOne($where = null, $having = null) {
		$res = $this->find($where, null, null, null, 1, $having);
		if (count($res) == 1) return $res[0];
		return null;
	}

	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$return      = array();
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->select($this->getTableName(), '*', $where, $group, $order, $offset, $limit, $having);

		foreach ($persistence as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	public function delete($where) {
		if($this->hasCascade) {
			$models = $this->find($where);
			foreach($models as $model) {
				foreach($model->getDeleteCascades() as $cascadeModel => $foreign_key) {
					sly_Service_Factory::getService($cascadeModel)->delete($foreign_key);
				}
			}
		}
		$persistence = sly_DB_Persistence::getInstance();
		return $persistence->delete($this->getTableName(), $where);
	}
}
