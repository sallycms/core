<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Dispatcher {
	protected $container;

	/**
	 * Constructor
	 *
	 * @param sly_Container $container
	 */
	public function __construct(sly_Container $container) {
		$this->container = $container;
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
	 * return the DI container identifier for a controller
	 *
	 * This identifier controls both how controllers are found and how their
	 * created instaces are stored. The implementation should not check for any
	 * existing service, but rather only check if $name is syntactically valid
	 * and then return the identifier.
	 *
	 * @param  string $name  controller name, e.g. 'login'
	 * @return string        identifier, e.g. 'sly-controller-backend-login'
	 */
	abstract public function getControllerIdentifier($name);

	/**
	 * create a controller instance
	 *
	 * This method is called if no container has been defined in the service. It
	 * should contruct the class name and then instantiate the controller.
	 *
	 * @param  string $name              controller name, e.g. 'login'
	 * @return sly_Controller_Interface  controller instance
	 */
	abstract protected function buildController($name);

	/**
	 * call an action on a controller
	 *
	 * @param  mixed  $controller  a controller name (string) or a prebuilt controller instance
	 * @param  string $action
	 * @return sly_Response
	 */
	public function dispatch($controller, $action) {
		$response = $this->tryController($controller, $action);

		// if we got a string, wrap it in the layout and then in the response object
		if (is_string($response)) {
			$response = $this->handleStringResponse($response);

			// now we really need a proper response object
			if (!($response instanceof sly_Response)) {
				throw new LogicException('handleStringResponse() must return a sly_Response instance.');
			}
		}

		// register the new response, if the controller returned one
		if ($response instanceof sly_Response) {
			$this->getContainer()->setResponse($response);
		}

		// if the controller returned another action, execute it
		if ($response instanceof sly_Response_Action) {
			$response = $response->execute($this);
		}

		return $response;
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
				$controller = $this->getController($controller, null);
			}

			// check if the action is valid
			$this->checkAction($controller, $action);

			// inject current request and container
			$this->setupController($controller);

			if (!$controller->checkPermission($action)) {
				throw new sly_Authorisation_Exception(t('page_not_allowed', $action, get_class($controller)), 403);
			}

			// generic controllers should have no safety net and *must not* throw exceptions.
			if ($controller instanceof sly_Controller_Generic) {
				return $this->runController($controller, 'generic', $action);
			}

			// classic controllers should have a basic exception handling provided by us.
			return $this->runController($controller, $action);
		}
		catch (Exception $e) {
			return $this->handleControllerError($e, $controller, $action);
		}
	}

	/**
	 * get controller by name
	 *
	 * @throws sly_Controller_Exception
	 * @param  string $name              the controller name (e.g. 'login')
	 * @param  string $action
	 * @return sly_Controller_Interface  the controller
	 */
	public function getController($name, $action = null) {
		$identifier = $this->getControllerIdentifier($name);
		$container  = $this->getContainer();

		if (!isset($container[$identifier])) {
			$container[$identifier] = $controller = $this->buildController($name);
		}
		else {
			$controller = $container[$identifier];
		}

		if (!($controller instanceof sly_Controller_Interface)) {
			throw new sly_Controller_Exception(t('does_not_implement', get_class($controller), 'sly_Controller_Interface'), 404);
		}

		if ($action) {
			$this->checkAction($controller, $action);
		}

		return $controller;
	}

	/**
	 * check if a class name points to an existing, non-abstract class
	 *
	 * @throws sly_Controller_Exception  if the class name is invalid
	 * @param  string $className
	 */
	protected function checkControllerClass($className) {
		if (!class_exists($className)) {
			throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
		}

		$reflector = new ReflectionClass($className);

		if ($reflector->isAbstract()) {
			throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
		}
	}

	/**
	 * check if an action's method exists and is not just inherited
	 *
	 * @throws sly_Controller_Exception  if the action is invalid
	 * @param  string $controllerOrClassName
	 * @param  string $action
	 */
	protected function checkAction($controller, $action) {
		$className = get_class($controller);
		$reflector = new ReflectionClass($className);
		$methods   = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $idx => $method) {
			if ($method->getDeclaringClass()->getName() === $className) {
				$methods[$idx] = strtolower($method->getName());
			}
			else {
				unset($methods[$idx]);
			}
		}

		$method = strtolower($action).'Action';

		if (!in_array(strtolower($method), $methods)) {
			throw new sly_Controller_Exception(t('unknown_action', $method, $className), 404);
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

	protected function setupController(sly_Controller_Interface $controller) {
		$container = $this->getContainer();

		// inject current request and container
		$controller->setRequest($container->getRequest());
		$controller->setContainer($container);
	}

	/**
	 * handle a controller that printed its output
	 *
	 * @param string $content  the controller's captured output
	 */
	protected function handleStringResponse($content) {
		// collect additional output (warnings and notices from the bootstrapping)
		while (ob_get_level()) $content = ob_get_clean().$content;

		$container  = $this->getContainer();
		$config     = $container->getConfig();
		$dispatcher = $container->getDispatcher();
		$appName    = $container->getApplicationName();
		$content    = $dispatcher->filter('OUTPUT_FILTER', $content, array('environment' => $appName));
		$useEtag    = $config->get('use_etag');
		$response   = $container->getResponse();

		if ($useEtag === true || $useEtag === $appName || (is_array($useEtag) && in_array($appName, $useEtag))) {
			$response->setEtag(substr(md5($content), 0, 12));
		}

		$response->setContent($content);
		$response->isNotModified();

		return $response;
	}

	protected function handleControllerError(Exception $e, $controller, $action) {
		// throw away all content (including notices and warnings)
		while (ob_get_level()) ob_end_clean();

		// call the system error handler
		$handler = $this->getContainer()->getErrorHandler();
		$handler->onCaughtException($e); // dies away (does not use sly_Response)
	}
}
