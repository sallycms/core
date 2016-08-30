<?php
/*
 * Copyright (c) 2016, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * User for Tests
 *
 * @author  zozi@webvariants.de
 * @ingroup tests
 */
class sly_Model_User_Test extends sly_Model_User {
	public function __construct() {
		$params = array(
			'id' => '1',
			'name' => 'tests',
			'description' => 'tests',
			'login' => 'admin',
			'password' => 'cryptohash',
			'status' => '1',
			'attributes' => '{}',
			'updateuser' => 'tstuser',
			'updatedate' => '2016-01-01 00:00:00',
			'createuser' => 'testuser',
			'createdate' => '2016-01-01 00:00:00',
			'lasttrydate' => '2016-01-01 00:00:00',
			'timezone' => 'Europe/Berlin',
			'revision' => '0'
		);

		parent::__construct($params);
	}
}
