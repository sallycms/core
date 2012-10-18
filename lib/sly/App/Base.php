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
			sly_Core::setResponse($pageResponse);
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
				$controller = $this->getController($className);
			}

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
		$user = sly_Core::isBackend() ? sly_Util_User::getCurrentUser() : null;

		if (sly_Core::isDeveloperMode() || ($user && $user->isAdmin())) {
			sly_Service_Factory::getTemplateService()->refresh();
			sly_Service_Factory::getModuleService()->refresh();
			sly_Service_Factory::getAssetService()->validateCache();
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
		$params     = array(
			'app'    => $this,
			'name'   => $name,
			'action' => $this->getCurrentAction()
		);

		sly_Core::dispatcher()->notify('SLY_CONTROLLER_FOUND', $controller, $params);

		if ($useCompatibility) {
			// backwards compatibility for pre-0.6 code
			sly_Core::dispatcher()->notify('PAGE_CHECKED', $name);
		}
	}

	/**
	 * handle a controller that printed its output
	 *
	 * @param sly_Response $response
	 * @param string       $content
	 * @param string       $appName
	 */
	protected function handleStringResponse(sly_Response $response, $content, $appName) {
		// collect additional output (warnings and notices from the bootstrapping)
		while (ob_get_level()) $content = ob_get_clean().$content;

		$config     = sly_Core::config();
		$dispatcher = sly_Core::dispatcher();
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
	 * @return sly_Controller_Interface  the controller
	 */
	protected function getController($className) {
		static $instances = array();

		if (!isset($instances[$className])) {
			if (!class_exists($className)) {
				throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
			}

			if (class_exists('ReflectionClass')) {
				$reflector = new ReflectionClass($className);

				if ($reflector->isAbstract()) {
					throw new sly_Controller_Exception(t('unknown_controller', $className), 404);
				}
			}

			$instance = new $className();

			if (!($instance instanceof sly_Controller_Interface)) {
				throw new sly_Controller_Exception(t('does_not_implement', $className, 'sly_Controller_Interface'), 404);
			}

			$instances[$className] = $instance;
		}

		return $instances[$className];
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

	abstract public function getControllerClassPrefix();

	abstract protected function handleControllerError(Exception $e, $controller, $action);
}
