<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_ConfigurationTest extends PHPUnit_Framework_TestCase {

	public function testLoadYamlFile() {
		$config = new sly_Configuration();
		$config->setContainer(sly_Core::getContainer());

		sly_Util_Configuration::loadYamlFile($config, SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'sly_local.yml', true);
		sly_Util_Configuration::loadYamlFile($config, SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'sly_project.yml', false);

		$this->assertArrayHasKey('driver', $config->get('database'));
		$this->assertArrayHasKey('templates', $config->get('/'));
	}

}
