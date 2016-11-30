<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_BaseTest extends PHPUnit_Extensions_Database_TestCase {
	protected $pdo;
	private $setup;

	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	public function getConnection() {
		if (!$this->pdo) {
			$conn      = sly_Core::getContainer()->get('sly-dbal-connection');
			$this->pdo = $conn->getWrappedConnection();
		}

		return $this->createDefaultDBConnection($this->pdo);
	}

	public function setUp() {
		parent::setUp();
		sly_Core::cache()->flush('sly', true);

		if (!$this->setup) {
			foreach ($this->getRequiredAddOns() as $addon) {
				$this->loadAddOn($addon);
			}

			// login the dummy user
			$service = sly_Core::getContainer()->getUserService();
			$user    = $service->findById(SLY_TESTING_USER_ID);
			$service->setCurrentUser($user);

			$this->setup = true;
		}
	}

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		$name = $this->getDataSetName();
		$comp = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array());

		if ($name !== null) {
			$core = new PHPUnit_Extensions_Database_DataSet_YamlDataSet(__DIR__.'/../../datasets/'.$name.'.yml');
			$comp->addDataSet($core);
		}
		
		if(sly_Core::config()->get('database/driver') === 'pgsql') {
			$this->getConnection()->getConnection()->query("SELECT setval('sly_user_id_seq', 2, FALSE);");

			if ($name === 'sally-demopage') {
				$this->getConnection()->getConnection()->query("SELECT setval('sly_article_slice_id_seq', 7, FALSE);");
				$this->getConnection()->getConnection()->query("SELECT setval('sly_clang_id_seq', 8, FALSE);");
				$this->getConnection()->getConnection()->query("SELECT setval('sly_slice_id_seq', 7, FALSE);");
			}
		}

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
	protected function getRequiredAddOns() {
		return array();
	}

	protected function loadAddOn($addon) {
		$service = sly_Core::getContainer()->getAddOnManagerService();
		$service->load($addon, true, sly_Core::getContainer());
	}

	/**
	 * @return string  dataset basename without extension
	 */
	abstract protected function getDataSetName();
}
