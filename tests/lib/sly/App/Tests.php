<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_App_Tests implements sly_App_Interface {
	protected $container;

	public function __construct(sly_Container $container) {
		$this->container = $container;
	}

	public function initialize() {
		$container = $this->getContainer();

		// refresh develop ressources
		$container->getTemplateService()->refresh();
		$container->getModuleService()->refresh();

		// add a dummy i18n
		$i18n = new sly_I18N('de', __DIR__);
		$container->setI18N($i18n);

		$container->setEnvironment('dev');

		$container->getUserService()->setCurrentUser(new sly_Model_User_Test());

		// clear current cache
		sly_Core::cache()->flush('sly');
	}

	public function run() {

	}

	public function getCurrentController() {

	}

	public function getCurrentAction() {
		return 'test';
	}

	public function getContainer() {
		return $this->container;
	}

	public function isBackend() {
		return true;
	}
}
