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
		$isSetup = sly_Core::isSetup();

		// boot addOns
		if (!$isSetup) sly_Core::loadAddOns();

		// register listeners
		sly_Core::registerListeners();

		// synchronize develop
		if (!$isSetup) $this->syncDevelopFiles();
	}

	/**
	 * call an action on a controller
	 *
	 * @param  mixed  $controller  a controller name (string) or a prebuilt controller instance
	 * @param  string $action
	 * @return sly_Response
	 */
	public function dispatch($controller, $action) {
		$pageResponse = $this->tryController($controller, $action);

		// register the new response, if the controller returned one
		if ($pageResponse instanceof sly_Response) {
			$this->getContainer()->setResponse($pageResponse);
		}

		// if the controller returned another action, execute it
		if ($pageResponse instanceof sly_Response_Action) {
			$pageResponse = $pageResponse->execute($this);
		}

		return $pageResponse;
	}

	/**
	 * call an action on a controller
	 *
	 * @param  mixed  $controller  a controller name (string) or a prebuilt controller instance
	 * @param  string $action
	 * @return sly_Response
	 */
	public function tryController($controller, $action) {
		// build controller instance and check permissions
		try {
			if (!($controller instanceof sly_Controller_Interface)) {
				$className  = $this->getControllerClass($controller);
				$controller = $this->getController($className, $action);
			}

			// inject current request and container
			$container = $this->getContainer();

			$controller->setRequest($container->getRequest());
			$controller->setContainer($container);

			if (!$controller->checkPermission($action)) {
				throw new sly_Authorisation_Exception(t('page_not_allowed', $action, get_class($controller)), 403);
			}
		}
		catch (Exception $e) {
			return $this->handleControllerError($e, $controller, $action);
		}

		// generic controllers should have no safety net and *must not* throw exceptions.
		if ($controller instanceof sly_Controller_Generic) {
			return $this->runController($controller, 'generic', $action);
		}

		// classic controllers should have a basic exception handling provided by us.
		try {
			return $this->runController($controller, $action);
		}
		catch (Exception $e) {
			return $this->handleControllerError($e, $controller, $action);
		}
	}

	/**
	 * call an action on a controller
	 *
	 * @param  mixed  $controller  a controller name (string) or a prebuilt controller instance
	 * @param  string $action
	 * @param  mixed  $param       a single parameter for the action method
	 * @return sly_Response
	 */
	protected function runController($controller, $action, $param = null) {
		ob_start();

		// prepare controller
		$method = $action.'Action';

		// run the action method
		if ($param === null) {
			$r = $controller->$method();
		}
		else {
			$r = $controller->$method($param);
		}

		if ($r instanceof sly_Response || $r instanceof sly_Response_Action) {
			ob_end_clean();
			return $r;
		}

		// collect output
		return ob_get_clean();
	}

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
	 * check if a controller exists
	 *
	 * @param  string $controller
	 * @return boolean
	 */
	public function isControllerAvailable($controller) {
		return class_exists($this->getControllerClass($controller));
	}

	/**
	 * return classname for &page=whatever
	 *
	 * It will return sly_Controller_System for &page=system
	 * and sly_Controller_System_Languages for &page=system_languages
	 *
	 * @param  string $controller
	 * @return string
	 */
	public function getControllerClass($controller) {
		$className = $this->getControllerClassPrefix();
		$parts     = explode('_', $controller);

		foreach ($parts as $part) {
			$className .= '_'.ucfirst($part);
		}

		return $className;
	}

	/**
	 * fire an event about the current controller
	 *
	 * This fires the SLY_CONTROLLER_FOUND event.
	 *
	 * @param boolean $useCompatibility  if true, PAGE_CHECKED will be fired as well
	 */
	protected function notifySystemOfController($useCompatibility = false) {
		$name       = $this->getCurrentControllerName();
		$controller = $this->getCurrentController();
		$dispatcher = $this->getContainer()->getDispatcher();
		$params     = array(
			'app'    => $this,
			'name'   => $name,
			'action' => $this->getCurrentAction()
		);

		$dispatcher->notify('SLY_CONTROLLER_FOUND', $controller, $params);

		if ($useCompatibility) {
			// backwards compatibility for pre-0.6 code
			$dispatcher->notify('PAGE_CHECKED', $name);
		}
	}

	/**
	 * handle a controller that printed its output
	 *
	 * @param sly_Response $response
	 * @param string       $content
	 */
	protected function handleStringResponse(sly_Response $response, $content) {
		// collect additional output (warnings and notices from the bootstrapping)
		while (ob_get_level()) $content = ob_get_clean().$content;

		$container  = $this->getContainer();
		$config     = $container->getConfig();
		$dispatcher = $container->getDispatcher();
		$appName    = $container->getApplicationName();
		$content    = $dispatcher->filter('OUTPUT_FILTER', $content, array('environment' => $appName));
		$etag       = substr(md5($content), 0, 12);
		$useEtag    = $config->get('USE_ETAG');

		if ($useEtag === true || $useEtag === $appName) {
			$response->setEtag($etag);
		}

		$response->setContent($content);
		$response->isNotModified();
	}

	/**
	 * get controller by name
	 *
	 * @throws sly_Controller_Exception
	 * @param  string $className
	 * @param  string $action
	 * @return sly_Controller_Interface  the controller
	 */
	protected function getController($className, $action = null) {
		static $instances = array();

		if (!isset($instances[$className])) {
			if (!class_exists($className)) {
				throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
			}

			$reflector = new ReflectionClass($className);

			if ($reflector->isAbstract()) {
				throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
			}

			$instance = new $className();

			if (!($instance instanceof sly_Controller_Interface)) {
				throw new sly_Controller_Exception(t('does_not_implement', $className, 'sly_Controller_Interface'), 404);
			}

			$instances[$className] = $instance;
		}

		if ($action) {
			$this->checkActionMethod($className, $action);
		}

		return $instances[$className];
	}

	protected function checkActionMethod($className, $action) {
		$reflector = new ReflectionClass($className);
		$methods   = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
		$parent    = $reflector->getParentClass();

		foreach ($methods as $idx => $method) {
			$methods[$idx] = $method->getName();
		}

		$own = $methods;

		if ($parent) {
			$pmethods = $parent->getMethods(ReflectionMethod::IS_PUBLIC);

			foreach ($pmethods as $idx => $method) {
				$pmethods[$idx] = $method->getName();
			}

			$own = array_diff($own, $pmethods);
		}

		$method = $action.'Action';

		if (!in_array($method, $own)) {
			throw new sly_Controller_Exception(t('unknown_action', $method, $className), 404);
		}
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

		$className  = $this->getControllerClass($name);
		$controller = $this->getController($className);

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

	abstract public function getControllerClassPrefix();

	abstract protected function handleControllerError(Exception $e, $controller, $action);
}
