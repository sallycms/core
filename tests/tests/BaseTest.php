<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_BaseTest extends PHPUnit_Extensions_Database_TestCase {
	protected $pdo;

	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	public function getConnection() {
		if (!$this->pdo) {
			$data = sly_Core::config()->get('DATABASE');
			$conn = sly_DB_PDO_Connection::getInstance($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD'], $data['NAME']);

			$this->pdo = $conn->getPDO();
		}

		return $this->createDefaultDBConnection($this->pdo, $data['NAME']);
	}

	public function setUp() {
		parent::setUp();
		sly_Core::cache()->flush('sly', true);

		foreach ($this->getRequiredComponents() as $comp) {
			$this->loadComponent($comp);
		}
	}

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		$name = $this->getDataSetName();
		$core = new PHPUnit_Extensions_Database_DataSet_YamlDataSet(dirname(__FILE__).'/../datasets/'.$name.'.yml');
		$comp = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array());
		$comp->addDataSet($core);

		foreach ($this->getAdditionalDataSets() as $ds) {
			$comp->addDataSet($ds);
		}

		return $comp;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalDataSets() {
		return array();
	}

	/**
	 * @return array
	 */
	protected function getRequiredComponents() {
		return array();
	}

	protected function loadComponent($component) {
		if (is_array($component)) {
			$service = sly_Service_Factory::getPluginService();
			$service->loadPlugin($component, true);
		}
		else {
			$service = sly_Service_Factory::getAddOnService();
			$service->loadAddOn($component, true);
		}
	}

	/**
	 * @return string  dataset basename without extension
	 */
	abstract protected function getDataSetName();
}
