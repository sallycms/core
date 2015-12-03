<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ConfigurationHandlerTest extends PHPUnit_Framework_TestCase {

	public function testHandler() {
		$container = sly_Core::getContainer();
		$handler   = new sly_Configuration_DatabaseImpl(SLY_CONFIGFOLDER, $container->get('sly-service-yaml'));

		$handler->setPersistence($container->getPersistence());

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
