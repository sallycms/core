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
	protected $filename; ///< string
	protected $dump;     ///< sly_DB_Dump

	public function __construct() {
		$this->reset();
	}

	/**
	 * @throws sly_Exception     if the dump is broken or missing
	 * @param  string $filename
	 */
	public function import($filename) {
		$this->reset($filename);

		// check preconditions
		$this->dump = new sly_DB_Dump($this->filename);

		$this->checkVersion();
		$this->checkPrefix();

		// fire event (could throw up)
		sly_Core::dispatcher()->notify('SLY_DB_IMPORTER_BEFORE', $this->dump, array(
			'filename' => $filename,
			'filesize' => filesize($filename)
		));

		// import dump
		$this->executeQueries();

		$flash   = sly_Core::getFlashMessage();
		$queries = count($this->dump->getQueries());
		$msg     = t('importer_database_imported').' '.t('importer_entry_count', $queries);

		$flash->addInfo($msg);

		// refresh cache
		sly_Core::dispatcher()->notify('SLY_DB_IMPORTER_AFTER', $this->dump, array(
			'filename' => $this->filename,
			'filesize' => filesize($this->filename)
		));

		sly_Core::clearCache();
	}

	/**
	 * @param string $filename
	 */
	protected function reset($filename = '') {
		$this->filename = $filename;
		$this->dump     = null;
	}

	/**
	 * @throws sly_Exception  when the versions don't match
	 */
	protected function checkVersion() {
		$dumpVersion = $this->dump->getVersion();
		$thisVersion = sly_Core::getVersion('X.Y.Y');

		if ($dumpVersion === null || !sly_Util_Versions::isCompatible($dumpVersion)) {
			throw new sly_Exception(t('importer_no_valid_import_file_version'));
		}
	}

	/**
	 * @throws sly_Exception  when no prefix was found
	 */
	protected function checkPrefix() {
		$prefix = $this->dump->getPrefix();

		if ($prefix === null) {
			throw new sly_Exception(t('importer_no_valid_import_file_prefix'));
		}
	}

	protected function executeQueries() {
		$sql = sly_DB_Persistence::getInstance();

		try {
			$this->dump->mapQueries(array($sql, 'query'));
		}
		catch (sly_DB_PDO_Exception $e) {
			throw new sly_Exception($e->getMessage(), $e->getCode());
		}

		$sql = null;
	}
}
