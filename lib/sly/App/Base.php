<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_App_Base implements sly_App_Interface {
	protected $container;
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
	 * initialize the app
	 */
	public function initialize() {
		// boot addOns
		$this->loadAddOns();

		// setup the stream wrappers
		$this->registerStreamWrapper();

		// register listeners
		sly_Core::registerListeners();

		// synchronize develop
		if (sly_Core::isDeveloperMode()) {
			$this->syncDevelopFiles();
		}
	}
	
	protected function loadAddons() {
		$container = $this->getContainer();

		$container->getAddOnManagerService()->loadAddOns($container);
		$container->getDispatcher()->notify('SLY_ADDONS_LOADED', $container);
	}

	/**
	 * synchronize develop files and asset cache
	 *
	 * This method will refresh the develop contents as well as validate the
	 * asset cache, if the site is in devmode or an admin is logged in.
	 */
	protected function syncDevelopFiles() {
		$container = $this->getContainer();

		$container->getTemplateService()->refresh();
		$container->getModuleService()->refresh();
	}

	/**
	 * fire an event about the current controller
	 *
	 * This fires the SLY_CONTROLLER_FOUND event.
	 */
	protected function notifySystemOfController() {
		$name       = $this->getCurrentControllerName();
		$controller = $this->getCurrentController();
		$dispatcher = $this->getContainer()->getDispatcher(); // event dispatcher, not the request dispatcher
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

		return $this->getDispatcher()->getController($name);
	}

	/**
	 * Get the absolute base URL to the current app's base URL
	 *
	 * @param  mixed   $forceProtocol  a concrete protocol like 'http' or null for the current one
	 * @return string
	 */
	public function getBaseUrl($forceProtocol = null) {
		$container = $this->getContainer();
		$request   = $container->getRequest();

		return $request->getAppBaseUrl($forceProtocol, $container);
	}

	protected function setDefaultTimezone() {
		$this->setTimezone((sly_Core::getTimezone() ?: @date_default_timezone_get()) ?: 'Europe/Berlin');
	}

	protected function setTimezone($timezone) {
		ini_set('date.timezone', $timezone);
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

	protected function registerStreamWrapper() {
		$container = $this->getContainer();
		$fsMap     = $container['sly-filesystem-map'];
		$fsService = $container['sly-service-filesystem'];

		$fsService->registerStreamWrapper($fsMap);
	}

	/**
	 * get request dispatcher
	 *
	 * @return sly_Dispatcher
	 */
	abstract protected function getDispatcher();

	abstract protected function prepareRouter(sly_Container $container);
	abstract protected function getControllerFromRequest(sly_Request $request);
	abstract protected function getActionFromRequest(sly_Request $request);

	abstract public function getCurrentControllerName();
}
