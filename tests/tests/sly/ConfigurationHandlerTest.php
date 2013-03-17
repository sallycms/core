<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ConfigurationHandlerTest extends PHPUnit_Framework_TestCase {

	public function testHandler() {
		$handler = new sly_Configuration_DatabaseImpl();
		$handler->setContainer(sly_Core::getContainer());

		$local = $handler->readLocal();
		$this->assertNotEmpty($local);

		$handler->writeLocal($local);
		$local2 = $handler->readLocal();

		$this->assertEquals($local2, $local);

		$project = array('unit' => 'test', 'array' => array('unit' => 'test'));
		$handler->writeProject($project);
		
		$project2 = $handler->readProject();
		$this->assertEquals($project2, $project);
	}
}
