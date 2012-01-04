<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Router_Base {
	protected $routes;
	protected $match;

	public function __construct(array $routes = array()) {
		$this->routes = array_unique($routes);
		$this->match  = false;
	}

	public function addRoute($route) {
		$this->routes[] = $route;
		$this->routes   = array_unique($this->routes);
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

			foreach ($this->routes as $route) {
				$regex = $this->buildRegex($route);
				$match = null;

				if (preg_match("#^$regex#u", $requestUri, $match)) {
					$this->match = $match;
					break;
				}
			}
		}

		return $this->match;
	}

	public function hasMatch() {
		if ($this->match === false) $this->match();
		return $this->match !== null;
	}

	public function get($key, $default = null) {
		if ($this->match === false) $this->match();
		if ($this->match === null) return $default;

		return isset($this->match[$key]) ? $this->match[$key] : $default;
	}

	public function getRequestUri() {
		if (!isset($_SERVER['REQUEST_URI'])) {
			throw new LogicException('Cannot route without a request URI.');
		}

		$host    = sly_Util_HTTP::getBaseUrl();     // 'http://example.com'
		$base    = sly_Util_HTTP::getBaseUrl(true); // 'http://example.com/sallyinstall'
		$request = $host.$_SERVER['REQUEST_URI'];   // 'http://example.com/sallyinstall/backend/system'

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
