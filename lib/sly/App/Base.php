<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_App_Base implements sly_App_Interface {
	protected $container;
	protected $dispatcher;
	protected $controller;
	protected $action;

	/**
	 * Constructor
	 *
	 * @param sly_Container $container
	 */
	public function __construct(sly_Container $container = null) {
		$this->container = $container ? $container : sly_Core::getContainer();
	}

	/**
	 * get DI container
	 *
	 * @return sly_Container
	 */
	public function getContainer() {
		return $this->container;
	}

	/**
	 * get request dispatcher
	 *
	 * @return sly_Dispatcher
	 */
	protected function getDispatcher() {
		if ($this->dispatcher === null) {
			$this->dispatcher = new sly_Dispatcher($this->getContainer(), $this->getControllerClassPrefix());
		}

		return $this->dispatcher;
	}

	/**
	 * initialize the app
	 */
	public function initialize() {
		$isSetup = sly_Core::isSetup();

		// boot addOns
		if (!$isSetup) sly_Core::loadAddOns();

		// register listeners
		sly_Core::registerListeners();

		// synchronize develop
		if (!$isSetup) $this->syncDevelopFiles();
	}

	/**
	 * synchronize develop files and asset cache
	 *
	 * This method will refresh the develop contents as well as validate the
	 * asset cache, if the site is in devmode or an admin is logged in.
	 */
	protected function syncDevelopFiles() {
		$user      = null;
		$container = $this->getContainer();

		if (sly_Core::isBackend()) {
			$user = $container->getUserService()->getCurrentUser();
		}

		if (sly_Core::isDeveloperMode() || ($user && $user->isAdmin())) {
			$container->getTemplateService()->refresh();
			$container->getModuleService()->refresh();
			$container->getAssetService()->validateCache();
		}
	}

	/**
	 * fire an event about the current controller
	 *
	 * This fires the SLY_CONTROLLER_FOUND event.
	 */
	protected function notifySystemOfController() {
		$name       = $this->getCurrentControllerName();
		$controller = $this->getCurrentController();
		$dispatcher = $this->getContainer()->getDispatcher();
		$params     = array(
			'app'    => $this,
			'name'   => $name,
			'action' => $this->getCurrentAction()
		);

		$dispatcher->notify('SLY_CONTROLLER_FOUND', $controller, $params);
	}

	/**
	 * get the current controller
	 *
	 * @return sly_Controller_Interface
	 */
	public function getCurrentController() {
		$name = $this->getCurrentControllerName();

		if (mb_strlen($name) === 0) {
			return null;
		}

		$dispatcher = $this->getDispatcher();
		$className  = $dispatcher->getControllerClass($name);
		$controller = $dispatcher->getController($className);

		return $controller;
	}

	protected function setDefaultTimezone($isSetup) {
		$timezone = $isSetup ? @date_default_timezone_get() : sly_Core::getTimezone();

		// fix badly configured servers where the get function doesn't even return a guessed default timezone
		if (empty($timezone)) {
			$timezone = sly_Core::getTimezone();
		}

		// set the determined timezone
		date_default_timezone_set($timezone);
	}

	protected function performRouting(sly_Request $request) {
		// create new router and hand it to all addOns
		$container = $this->getContainer();
		$router    = $this->prepareRouter($container);

		if (!($router instanceof sly_Router_Interface)) {
			throw new LogicException('Expected a sly_Router_Interface as the result from prepareRouter().');
		}

		// use the router to prepare the request and setup proper query string values
		$retval = $router->match($request);

		// setup app state
		$this->controller = $this->getControllerFromRequest($request);
		$this->action     = $this->getActionFromRequest($request);

		return $retval;
	}

	abstract protected function prepareRouter(sly_Container $container);
	abstract protected function getControllerFromRequest(sly_Request $request);
	abstract protected function getActionFromRequest(sly_Request $request);

	abstract public function getControllerClassPrefix();
	abstract public function getCurrentControllerName();
	abstract public function getCurrentAction();
}
