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
class sly_Configuration_DatabaseImpl implements sly_Configuration_Reader, sly_Configuration_Writer {
	protected $configDir;   ///< string
	protected $persistence; ///< sly_DB_PDO_Persistence
	protected $fileService; ///< sly_Service_File_Base

	public function __construct($configDirectory, sly_Service_File_Base $fileService, sly_DB_PDO_Persistence $persistence = null) {
		$this->configDir   = rtrim($configDirectory, '/\\');
		$this->fileService = $fileService;
		$this->persistence = $persistence;
	}

	public function setPersistence(sly_DB_PDO_Persistence $persistence) {
		$this->persistence = $persistence;
	}

	public function writeLocal(array $data) {
		$this->fileService->dump($this->configDir.DIRECTORY_SEPARATOR.'sly_local.yml', $data);
	}

	public function writeProject(array $data) {
		$this->checkPersistence();

		$db = $this->persistence;

		$db->transactional(function() use ($db, $data) {
			$db->delete('config');

			foreach ($data as $id => $value) {
				$value = json_encode($value);
				$db->insert('config', compact('id', 'value'));
			}
		});
	}

	public function readLocal() {
		try {
			return $this->fileService->load($this->configDir.DIRECTORY_SEPARATOR.'sly_local.yml');
		}
		catch (sly_Exception $e) {
			return array();
		}
	}

	public function readProject() {
		$this->checkPersistence();

		$db     = $this->persistence;
		$result = array();

		try {
			$db->select('config');

			foreach ($db as $row) {
				$result[$row['id']] = json_decode($row['value'], true);
			}
		}
		catch (sly_DB_Exception $e) {
			// pass
		}

		return $result;
	}

	protected function checkPersistence() {
		if (!$this->persistence) {
			throw new LogicException('Persistence must be set before project configuration can be handled.');
		}
	}
}
