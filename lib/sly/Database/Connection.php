<?php
/*
 * Copyright (c) 2016, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace sly\Database;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\VersionAwarePlatformDriver;

class Connection extends DoctrineConnection {
	protected $prefix;

	public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null) {
		$this->prefix = $params['table_prefix'];

		parent::__construct($params, $driver, $config, $eventManager);
	}

	public function getDatabasePlatformVersion() {
		// Driver does not support version specific platforms.
		if (!$this->_driver instanceof VersionAwarePlatformDriver) {
			return null;
		}

		// Explicit platform version requested (supersedes auto-detection).
		if (isset($this->_params['serverVersion'])) {
			return $this->_params['serverVersion'];
		}

		// If not connected, we need to connect now to determine the platform version.
		if (null === $this->_conn) {
			$this->connect();
		}

		// Automatic platform version detection.
		if ($this->_conn instanceof ServerInfoAwareConnection &&
				!$this->_conn->requiresQueryForServerVersion()
		) {
			return $this->_conn->getServerVersion();
		}

		// Unable to detect platform version.
		return null;
	}

	public function getPrefix() {
		return $this->prefix;
	}

	public function getTable($tableExpression) {
		return $this->getDatabasePlatform()->quoteIdentifier($this->getPrefix() . $tableExpression);
	}
	
	public function lastInsertId($seqName = null)
    {
		$params = $this->getParams();
		
		if ($params['driver'] === 'pdo_pgsql') {
			$seqName = $seqName.'_id_seq';
		}
		
        return parent::lastInsertId($seqName);
    }
}
