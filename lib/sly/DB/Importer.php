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
 * @ingroup database
 */
class sly_DB_Importer {
	protected $persistence; ///< sly_DB_PDO_Persistence
	protected $dispatcher;  ///< sly_Event_IDispatcher

	public function __construct(sly_DB_PDO_Persistence $persistence, sly_Event_IDispatcher $dispatcher) {
		$this->persistence = $persistence;
		$this->dispatcher  = $dispatcher;
	}

	/**
	 * @throws sly_Exception     if the dump is broken or missing
	 * @param  string $filename
	 */
	public function import($filename) {
		return $this->importDump(new sly_DB_Dump($filename));
	}

	/**
	 * @throws sly_Exception      if the dump is broken or missing
	 * @param  sly_DB_Dump $dump
	 */
	public function importDump(sly_DB_Dump $dump) {
		// check preconditions
		$this->checkVersion($dump);
		$this->checkPrefix($dump);

		// fire event (could throw up)
		$this->dispatcher->notify('SLY_DB_IMPORTER_BEFORE', $dump);

		// import dump
		$this->executeQueries($dump);

		// notify system
		$this->dispatcher->notify('SLY_DB_IMPORTER_AFTER', $dump);
	}

	/**
	 * @throws sly_Exception      when the versions don't match
	 * @param  sly_DB_Dump $dump
	 */
	protected function checkVersion(sly_DB_Dump $dump) {
		$dumpVersion = $dump->getVersion();
		$thisVersion = sly_Core::getVersion('X.Y.Y');

		if ($dumpVersion === null || !sly_Util_Versions::isCompatible($dumpVersion)) {
			throw new sly_Exception(t('importer_no_valid_import_file_version'));
		}
	}

	/**
	 * @throws sly_Exception      when no prefix was found
	 * @param  sly_DB_Dump $dump
	 */
	protected function checkPrefix(sly_DB_Dump $dump) {
		$prefix = $dump->getPrefix();

		if ($prefix === null) {
			throw new sly_Exception(t('importer_no_valid_import_file_prefix'));
		}
	}

	/**
	 * @throws sly_Exception      if a database error occurs
	 * @param  sly_DB_Dump $dump
	 */
	protected function executeQueries(sly_DB_Dump $dump) {
		try {
			$dump->mapQueries(array($this->persistence, 'query'));
		}
		catch (sly_DB_PDO_Exception $e) {
			throw new sly_Exception($e->getMessage(), $e->getCode());
		}
	}
}
