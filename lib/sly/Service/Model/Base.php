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
 * @ingroup service
 */
abstract class sly_Service_Model_Base {
	protected $tablename;          ///< string
	protected $hasCascade = false; ///< boolean

	/**
	 * @param  array $array
	 * @return sly_Model_Base
	 */
	protected abstract function makeInstance(array $params);

	/**
	 * @return string
	 */
	protected function getTableName() {
		return $this->tablename;
	}

	/**
	 * @param  array  $where
	 * @param  string $having
	 * @return sly_Model_Base
	 */
	public function findOne($where = null, $having = null) {
		$res = $this->find($where, null, null, null, 1, $having);
		if (count($res) == 1) return $res[0];
		return null;
	}

	/**
	 * @param  array  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$return      = array();
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->select($this->getTableName(), '*', $where, $group, $order, $offset, $limit, $having);

		foreach ($persistence as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	/**
	 * @param  mixed $where
	 * @return int
	 */
	public function delete($where) {
		if ($this->hasCascade) {
			$models = $this->find($where);

			foreach ($models as $model) {
				foreach ($model->getDeleteCascades() as $cascadeModel => $foreign_key) {
					sly_Service_Factory::getService($cascadeModel)->delete($foreign_key);
				}
			}
		}

		$persistence = sly_DB_Persistence::getInstance();
		return $persistence->delete($this->getTableName(), $where);
	}

	/**
	 * @param  array  $where
	 * @param  string $group
	 * @return array
	 */
	public function count($where = null, $group = null) {
		$count       = array();
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->select($this->getTableName(), 'COUNT(*)', $where, $group);

		foreach ($persistence as $row) {
			$count = reset($row);
		}

		return $count;
	}
}
