<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_App_Tests implements sly_App_Interface {
	protected $userID;
	protected $container;

	public function __construct(sly_Container $container, $userID) {
		$this->container = $container;
		$this->userID    = $userID;
	}

	public function initialize() {
		$container = $this->getContainer();

		// login the dummy user
		$service = $container->getUserService();
		$user    = $service->findById($this->userID);
		$service->setCurrentUser($user);

		// refresh develop ressources
		$container->getTemplateService()->refresh();
		$container->getModuleService()->refresh();

		// add a dummy i18n
		$i18n = new sly_I18N('de', __DIR__);
		$container->setI18N($i18n);

		$container->setEnvironment('dev');

		$config = $container->getConfig();
		$config->set('/', sly_Util_YAML::load(SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'sly_project.yml'));

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
