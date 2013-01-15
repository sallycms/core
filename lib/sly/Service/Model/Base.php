<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	protected $persistence;        ///< sly_DB_Persistence

	public function __construct(sly_DB_Persistence $persistence = null) {
		$this->persistence = $persistence ? $persistence : sly_DB_Persistence::getInstance();
	}

	/**
	 * @param  array $array
	 * @return sly_Model_Base
	 */
	abstract protected function makeInstance(array $params);

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
		return count($res) === 1 ? $res[0] : null;
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
		$return  = array();
		$db      = $this->getPersistence();

		$db->select($this->getTableName(), '*', $where, $group, $order, $offset, $limit, $having);

		foreach ($db as $row) {
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
			$models    = $this->find($where);
			$container = sly_Core::getContainer();

			foreach ($models as $model) {
				foreach ($model->getDeleteCascades() as $cascadeModel => $foreign_key) {
					$container->getService($cascadeModel)->delete($foreign_key);
				}
			}
		}

		return $this->getPersistence()->delete($this->getTableName(), $where);
	}

	/**
	 * @param  array  $where
	 * @param  string $group
	 * @return array
	 */
	public function count($where = null, $group = null) {
		$count = array();
		$db    = $this->getPersistence();

		$db->select($this->getTableName(), 'COUNT(*)', $where, $group);

		foreach ($db as $row) {
			$count = (int) reset($row);
		}

		return $count;
	}

	protected function getActor(sly_Model_User $user = null, $methodName = null) {
		if ($user === null) {
			$user = sly_Util_User::getCurrentUser();

			if ($user === null) {
				throw new sly_Exception($methodName ? t('operation_requires_user_context', $methodName) : t('an_operation_requires_user_context'));
			}
		}

		return $user;
	}

	/**
	 * gets a persistence object
	 *
	 * @return sly_DB_PDO_Persistence
	 */
	protected function getPersistence() {
		return $this->persistence;
	}
}
