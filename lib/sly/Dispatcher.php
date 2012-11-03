<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Dispatcher {
	protected $container;
	protected $prefix;

	/**
	 * Constructor
	 *
	 * @param sly_Container $container
	 */
	public function __construct(sly_Container $container, $containerClassPrefix) {
		$this->container = $container;
		$this->prefix    = $containerClassPrefix;
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
				$className  = $this->getControllerClass($controller);
				$controller = $this->getController($className, $action);
			}

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
	 * @param  string $className
	 * @param  string $action
	 * @return sly_Controller_Interface  the controller
	 */
	public function getController($className, $action = null) {
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

	/**
	 * check if a controller exists
	 *
	 * @param  string $controller  controller name like 'structure'
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
	 * @param  string $controller  controller name like 'structure'
	 * @return string
	 */
	public function getControllerClass($controller) {
		$className = $this->prefix;
		$parts     = explode('_', $controller);

		foreach ($parts as $part) {
			$className .= '_'.ucfirst($part);
		}

		return $className;
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

	/**
	 * check if an action's method exists and is not just inherited
	 *
	 * @throws sly_Controller_Exception  if the action is invalid
	 * @param  string $className
	 * @param  string $action
	 */
	protected function checkActionMethod($className, $action) {
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

		$method = $action.'action';

		if (!in_array($method, $methods)) {
			throw new sly_Controller_Exception(t('unknown_action', $method, $className), 404);
		}
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
		$useEtag    = $config->get('USE_ETAG');
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
		$handler->handleException($e); // dies away (does not use sly_Response)
	}
}
