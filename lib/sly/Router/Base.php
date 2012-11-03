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

	public function __construct(array $routes = array()) {
		$this->routes = $routes;
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

	public function match(sly_Request $request) {
		$requestUri = $this->getRequestUri($request);

		foreach ($this->routes as $route => $values) {
			$regex = $this->buildRegex($route);
			$match = null;

			if (preg_match("#^$regex#u", $requestUri, $match)) {
				$this->setupRequest($request, $match, $values);
				return true;
			}
		}

		return false;
	}

	protected function setupRequest(sly_Request $request, array $routeMatch, array $routeValues) {
		foreach ($routeValues as $key => $value) {
			$request->get->set($key, $value);
		}

		foreach ($routeMatch as $key => $value) {
			if (ctype_digit($key)) {
				$key = 'match'.$key;
			}

			$request->get->set($key, $value);
		}
	}

	protected function getRequestUri(sly_Request $request) {
		$requestUri = $request->getRequestUri();

		if (empty($requestUri)) {
			throw new LogicException('Cannot route without a request URI.');
		}

		$host    = $request->getBaseUrl();     // 'http://example.com'
		$base    = $request->getBaseUrl(true); // 'http://example.com/sallyinstall'
		$request = $host.$requestUri;          // 'http://example.com/sallyinstall/backend/system'

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
		$ident = '[a-z_][a-z0-9-_]*';
		$regex = preg_replace("#:($ident)#iu", "(?P<\$1>$ident)", $route);

		return str_replace('#', '\\#', $regex);
	}
}
