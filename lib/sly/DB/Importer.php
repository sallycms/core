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
 * @ingroup database
 */
class sly_DB_Importer {
	protected $filename;     ///< string
	protected $returnValues; ///< array
	protected $dump;         ///< sly_DB_Dump

	public function __construct() {
		$this->reset();
	}

	/**
	 * @param string $filename
	 */
	protected function reset($filename = '') {
		$this->returnValues['state']   = false;
		$this->returnValues['message'] = '';

		if (empty($filename) || substr($filename, -4) != '.sql') {
			$this->returnValues['message'] = t('importer_no_import_file_chosen_or_wrong_version');
			return;
		}

		$this->dump     = null;
		$this->filename = $filename;
	}

	/**
	 * @param  string $filename
	 * @return array             array with state and message keys
	 */
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

		// Cache erneuern, wenn alles OK lief

		if (empty($error)) {
			$msg = $this->regenerateCache($msg);
		}

		$this->returnValues['message'] = $msg;
		return $this->returnValues;
	}

	/**
	 * @return boolean
	 */
	protected function prepareImport() {
		try {
			$this->dump = new sly_DB_Dump($this->filename);

			$this->checkVersion();
			$this->checkPrefix();

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @throws sly_Exception  when the versions don't match
	 */
	protected function checkVersion() {
		$dumpVersion = $this->dump->getVersion();
		$thisVersion = sly_Core::getVersion('X.Y');

		if ($dumpVersion === false || $dumpVersion != $thisVersion) {
			$this->returnValues['message'] = t('importer_no_valid_import_file_version', $dumpVersion, $thisVersion);
			throw new sly_Exception('bad version');
		}
	}

	/**
	 * @throws sly_Exception  when no prefix was found
	 */
	protected function checkPrefix() {
		$prefix = $this->dump->getPrefix();

		if ($prefix === false) {
			$this->returnValues['message'] = t('importer_no_valid_import_file_prefix');
			throw new sly_Exception('bad prefix');
		}
	}

	/**
	 * @return array  list of errors
	 */
	protected function executeQueries() {
		$queries = $this->dump->getQueries();
		$sql     = sly_DB_Persistence::getInstance();
		$error   = array();

		foreach ($queries as $qry) {
			try {
				$sql->exec($qry);
			}
			catch (sly_DB_PDO_Exception $e) {
				$error[] = $e->getMessage();
			}
		}

		return $error;
	}

	/**
	 * @param  string $msg
	 * @return string
	 */
	protected function regenerateCache($msg) {
		$msg = sly_Core::dispatcher()->filter('SLY_DB_IMPORTER_AFTER', $msg, array(
			'dump'     => $this->dump,
			'filename' => $this->filename,
			'filesize' => filesize($this->filename)
		));

		$this->returnValues['state'] = true;
		return $msg.sly_Core::clearCache();
	}
}
