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
 * @ingroup database
 */
class sly_DB_Importer {
	protected $filename;
	protected $returnValues;
	protected $dump;

	public function __construct() {
		$this->reset();
	}

	protected function reset($filename = '') {
		$this->returnValues['state']   = false;
		$this->returnValues['message'] = '';

		if (empty($filename) || substr($filename, -4) != '.sql') {
			$this->returnValues['message'] = t('importer_no_import_file_chosen_or_wrong_version');
			return $this->returnValues;
		}

		$this->dump     = null;
		$this->filename = $filename;
	}

	public function import($filename) {
		$this->reset($filename);

		// Vorbedingungen abtesten

		if (!$this->prepareImport()) {
			return $this->returnValues;
		}

		$msg   = '';
		$error = array();

		// Extensions auslösen

		$msg = sly_Core::dispatcher()->filter('SLY_DB_IMPORTER_BEFORE', $msg, array(
			'dump'     => $this->dump,
			'filename' => $filename,
			'filesize' => filesize($filename)
		));

		// Import durchführen

		$error = $this->executeQueries();

		if (!empty($error)) {
			$this->returnValues['message'] = implode("<br />\n", $error);
			return $this->returnValues;
		}

		$queries = count($this->dump->getQueries());
		$msg    .= t('importer_database_imported').'. '.t('importer_entry_count', $queries).'<br />';

		// User-Tabelle ggf. anlegen, falls nicht vorhanden

		try {
			$this->checkForUserTable();
		} catch(sly_DB_PDO_Exception $e) {
			$error = $e->getMessage();
		}

		// Cache erneuern, wenn alles OK lief

		if (empty($error)) {
			$msg = $this->regenerateCache($msg);
		}

		$this->returnValues['message'] = $msg;
		return $this->returnValues;
	}

	protected function prepareImport() {
		try {
			$this->dump = new sly_DB_Dump($this->filename);

			$this->checkVersion();
			$this->checkPrefix();
			$this->checkCharset();

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	protected function checkVersion() {
		$dumpVersion = $this->dump->getVersion();
		$thisVersion = sly_Core::getVersion('X.Y');

		if ($dumpVersion === false || $dumpVersion != $thisVersion) {
			$this->returnValues['message'] = t('importer_no_valid_import_file_version', $dumpVersion, $thisVersion);
			throw new sly_Exception('bad version');
		}
	}

	protected function checkPrefix() {
		$prefix = $this->dump->getPrefix();

		if ($prefix === false) {
			$this->returnValues['message'] = t('importer_no_valid_import_file_prefix');
			throw new sly_Exception('bad prefix');
		}
	}

	protected function checkCharset() {
		$dumpCharset = $this->dump->getCharset();

		if ($dumpCharset === false) {
			$this->returnValues['message'] = t('importer_no_valid_charset');
			throw new sly_Exception('bad charset');
		}

//		$thisCharset = t('htmlcharset');
//
//		if ($dumpCharset != $thisCharset) {
//			$this->returnValues['message'] = t('importer_charset_mismatch');
//			throw new sly_Exception('bad charset');
//		}
	}

	protected function executeQueries() {
		$queries = $this->dump->getQueries();

		$sql   = sly_DB_Persistence::getInstance();
		$error = array();

		foreach ($queries as $qry) {
			try {
				$sql->exec($qry);
			} catch (sly_DB_PDO_Exception $e) {
				$error[] = $e->getMessage();
			}
		}

		return $error;
	}

	protected function checkForUserTable() {
		$prefix   = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$hasTable = sly_DB_Persistence::getInstance()->listTables($prefix.'user');

		if (!$hasTable) {
			$createStmt = file_get_contents(SLY_INCLUDE_PATH.'/install/user.sql');
			$createStmt = str_replace('%PREFIX%', $prefix, $createStmt);

			$db = sly_DB_Persistence::getInstance();
			$db->query($createStmt);
			return $db;
		}

		return true;
	}

	protected function regenerateCache($msg) {
		$msg = sly_Core::dispatcher()->filter('SLY_DB_IMPORTER_AFTER', $msg, array(
			'dump'     => $this->dump,
			'filename' => $this->filename,
			'filesize' => filesize($this->filename)
		));

		$this->returnValues['state'] = true;
		return $msg.rex_generateAll();
	}
}
