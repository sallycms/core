<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author christoph@webvariants.de
 * @since  0.6
 */
class sly_Router_Base implements sly_Router_Interface {
	protected $routes;
	protected $match;

	public function __construct(array $routes = array()) {
		$this->routes = $routes;
		$this->match  = false;
	}

	public function addRoute($route, array $values) {
		$this->routes[$route] = $values;
	}

	public function clearRoutes() {
		$this->routes = array();
	}

	public function getRoutes() {
		return $this->routes;
	}

	public function match() {
		if ($this->match === false) {
			$this->match = null;
			$requestUri  = $this->getRequestUri();

			foreach ($this->routes as $route => $values) {
				$regex = $this->buildRegex($route);
				$match = null;

				if (preg_match("#^$regex#u", $requestUri, $match)) {
					$this->match = array($match, $values);
					break;
				}
			}
		}

		return $this->match;
	}

	public function hasMatch() {
		return is_array($this->match());
	}

	public function getController() {
		$controller = $this->get('controller', null);

		if ($controller === null) {
			throw new sly_Exception('Matched route contains neither a :controller placeholder not a controller value.');
		}

		return $controller;
	}

	public function getAction() {
		return $this->get('action', 'index');
	}

	public function get($key, $default = null) {
		$this->match();

		if (!is_array($this->match)) {
			return $default;
		}

		list($match, $values) = $this->match;

		if (array_key_exists($key, $match)) {
			return $match[$key];
		}

		return array_key_exists($key, $values) ? $values[$key] : $default;
	}

	public function getRequestUri() {
		$requestUri = sly_Core::getRequest()->getRequestUri();

		if (empty($requestUri)) {
			throw new LogicException('Cannot route without a request URI.');
		}

		$host    = sly_Util_HTTP::getBaseUrl();     // 'http://example.com'
		$base    = sly_Util_HTTP::getBaseUrl(true); // 'http://example.com/sallyinstall'
		$request = $host.$requestUri;               // 'http://example.com/sallyinstall/backend/system'

		if (mb_substr($request, 0, mb_strlen($base)) !== $base) {
			throw new LogicException('Base URI mismatch.');
		}

		$req = mb_substr($request, mb_strlen($base)); // '/backend/system'

		// remove query string
		if (($pos = mb_strpos($req, '?')) !== false) {
			$req = mb_substr($req, 0, $pos);
		}

		// remove script name
		if (sly_Util_String::endsWith($req, '/index.php')) {
			$req = mb_substr($req, 0, -10);
		}

		return rtrim($req, '/');
	}

	// transform '/:controller/' into '/(?P<controller>[a-z0-9_-])/'
	protected function buildRegex($route) {
		$route = rtrim($route, '/');
		$ident = '[a-z_][a-z0-9_]*';
		$regex = preg_replace("#:($ident)#iu", "(?P<\$1>[a-z0-9_]*)", $route);

		return str_replace('#', '\\#', $regex);
	}
}
