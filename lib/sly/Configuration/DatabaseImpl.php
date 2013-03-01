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
 * System Configuration Reader Interface Implementation
 *
 * @ingroup core
 */
class sly_Configuration_DatabaseImpl implements sly_Configuration_Reader, sly_Configuration_Writer, sly_ContainerAwareInterface {
	protected $container; ///< sly_Container

	public function __construct() {

	}

	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	public function writeLocal(array $data) {
		sly_Util_YAML::dump(SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'sly_local.yml', $data);
	}

	public function writeProject(array $data) {
		$db      = $this->container->getPersistence();
		$ownTrx  = !$db->isTransRunning();

		if ($ownTrx) {
			$db->beginTransaction();
		}
		try {
			$db->delete('config');
			foreach ($data as $id => $value) {
				$value = json_encode($value);
				$db->insert('config', compact('id', 'value'));
			}
			if ($ownTrx) {
					$db->commit();
				}
			}
		catch (Exception $e) {
			if ($ownTrx) {
				$db->rollBack();
			}

			throw $e;
		}
	}

	public function readLocal() {
		return sly_Util_YAML::load(SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'sly_local.yml');
	}

	public function readProject() {
		$result = array();
		$db     = $this->container->getPersistence();

		$db->select('config');

		foreach ($db as $row) {
			$result[$row['id']] = json_decode($row['value'], true);
		}

		return $result;
	}
}
