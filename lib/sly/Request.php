<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * HTTP request
 *
 * This is basically a stripped down version of Symfony2's Request class,
 * taking away the PHP 5.3, formats and path stuff.
 */
class sly_Request {
	protected static $trustProxy = true;

	public $get;
	public $post;
	public $server;
	public $files;
	public $cookies;
	public $headers;

	protected $content;
	protected $languages;
	protected $charsets;
	protected $acceptableContentTypes;
	protected $requestUri;
	protected $method;

	/**
	 * Constructor
	 *
	 * @param array  $get      the GET parameters
	 * @param array  $post     the POST parameters
	 * @param array  $cookies  the COOKIE parameters
	 * @param array  $files    the FILES parameters
	 * @param array  $server   the SERVER parameters
	 * @param string $content  the raw body data
	 */
	public function __construct(array $get = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null) {
		$this->initialize($get, $post, $cookies, $files, $server, $content);
	}

	public function get($key, $type, $default = null) {
		return $this->get->get($key, $type, $default);
	}

	public function post($key, $type, $default = null) {
		return $this->post->get($key, $type, $default);
	}

	public function cookie($key, $type, $default = null) {
		return $this->cookies->get($key, $type, $default);
	}

	public function server($key, $default = null) {
		return $this->server->get($key, 'string', $default);
	}

	public function header($key, $default = null) {
		return $this->headers->get($key, 'string', $default);
	}

	public function request($key, $type, $default = null) {
		$request = $this->buildRequestArray(
			$this->get->all(),
			$this->post->all(),
			$this->cookies->all()
		);

		return sly_setarraytype($request, $key, $type, $default);
	}

	public function getArray($key, $type, $default = array()) {
		return $this->getParameterArray($this->get, $key, $type, $default);
	}

	public function postArray($key, $type, $default = array()) {
		return $this->getParameterArray($this->post, $key, $type, $default);
	}

	public function requestArray($key, $type, $default = array()) {
		$source = $this->post->has($key) ? $this->post : $this->get;
		return $this->getParameterArray($source, $key, $type, $default);
	}

	public function getParameterArray(sly_Util_ArrayObject $source, $key, $type, $default) {
		$cast   = $source->has($key);
		$values = sly_makeArray($source->get($key, 'raw', $default));

		foreach ($values as $idx => $value) {
			if (is_array($value)) {
				unset($values[$idx]);
			}
			elseif ($cast) {
				$values[$idx] = sly_settype($value, $type);
			}
		}

		return $values;
	}

	protected function buildRequestArray(array $get, array $post, array $cookie) {
		$requests     = array('g' => $get, 'p' => $post, 'c' => $cookie);
		$requestOrder = sly_ini_get('request_order') ? sly_ini_get('request_order') : sly_ini_get('variable_order');
		$requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder));
		$request      = array();

		if (!$requestOrder) {
			 $requestOrder = 'gp';
		}

		foreach (str_split($requestOrder) as $order) {
			$request = array_merge($request, $requests[$order]);
		}

		return $request;
	}

	/**
	 * Get the absolute base URL to the project's root (frontend)
	 *
	 * @param  boolean $addScriptPath
	 * @param  mixed   $forceProtocol  a concrete protocol like 'http' or null for the current one
	 * @param  mixed   $forcePort      a concrete port or null for the current one
	 * @return string
	 */
	public function getBaseUrl($addScriptPath = false, $forceProtocol = null, $forcePort = null) {
		$protocol = $forceProtocol === null ? $this->getScheme() : $forceProtocol;
		$host     = $this->getHost();
		$port     = $this->getPort();
		$path     = $addScriptPath ? $this->getBasePath() : '';

		if ($forcePort === true) {
			$port = ':'.$port;
		}
		else {
			if ($forcePort === false) {
				$port = '';
			}
			else {
				$secure = $this->isSecure();
				$port   = (($port === 80 && !$secure) || ($port === 443 && $secure)) ? '' : ':'.$port;
			}
		}

		return rtrim(sprintf('%s://%s%s%s', $protocol, $host, $port, $path), '/');
	}

	/**
	 * Get the base URL path to the project's root (e.g. '/myproject')
	 *
	 * @return string
	 */
	public function getBasePath() {
		// in CLI, the SCRIPT_NAME would be something like 'C:\xamp\php\phpunit'...
		if (PHP_SAPI === 'cli') {
			return '/sally';
		}

		$path = $this->getScriptName();
		$path = dirname($path); // '/foo' or '/foo/sally/backend'
		$path = str_replace('\\', '/', $path);

		return $path ? $path : '/';
	}

	/**
	 * Get the absolute base URL to the current app's base URL
	 *
	 * @param  mixed   $forceProtocol  a concrete protocol like 'http' or null for the current one
	 * @return string
	 */
	public function getAppBaseUrl($forceProtocol = null, sly_Container $container = null) {
		$container = $container ? $container : sly_Core::getContainer();
		$base      = $this->getBaseUrl(true, $forceProtocol);
		$appBase   = $container->getApplicationBaseUrl();

		return $base.$appBase;
	}

	/*
	 * The following methods are mostly derived from code of Symfony2 (2.1.2)
	 * Code subject to the MIT license (http://symfony.com/doc/2.1/contributing/code/license.html).
	 * Copyright (c) 2004-2012 Fabien Potencier
	 */

	/**
	 * Sets the parameters for this request
	 *
	 * This method also re-initializes all properties.
	 *
	 * @param array  $get      the GET parameters
	 * @param array  $post     the POST parameters
	 * @param array  $cookies  the COOKIE parameters
	 * @param array  $files    the FILES parameters
	 * @param array  $server   the SERVER parameters
	 * @param string $content  the raw body data
	 */
	public function initialize(array $get = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null) {
		$this->get        = new sly_Util_ArrayObject($get);
		$this->post       = new sly_Util_ArrayObject($post);
		$this->cookies    = new sly_Util_ArrayObject($cookies);
		$this->files      = $files;
		$this->server     = new sly_Util_ArrayObject($server, sly_Util_ArrayObject::NORMALIZE_UPPERCASE);
		$this->headers    = $this->getHeadersFromServer($this->server);
		$this->content    = $content;
		$this->languages  = null;
		$this->charsets   = null;
		$this->requestUri = null;
		$this->method     = null;

		$this->acceptableContentTypes = null;
	}

	/**
	 * Creates a new request with values from PHP's super globals
	 *
	 * @return sly_Request  a new request
	 */
	public static function createFromGlobals() {
		$request     = new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
		$contentType = $request->getContentType();
		$method      = $request->getMethod();
		$isUrlEnc    = strpos($contentType, 'application/x-www-form-urlencoded') === 0;

		if ($isUrlEnc && in_array($method, array('PUT', 'DELETE', 'PATCH'))) {
			parse_str($request->getContent(), $data);
			$request->post = new sly_Util_ArrayObject($data);
		}

		return $request;
	}

	/**
	 * Clones the current request
	 */
	public function __clone() {
		$this->get     = clone $this->get;
		$this->post    = clone $this->post;
		$this->cookies = clone $this->cookies;
		$this->server  = clone $this->server;
		$this->headers = clone $this->headers;
	}

	/**
	 * Returns the request as a string.
	 *
	 * @return string The request
	 */
	public function __toString() {
		return sprintf('%s %s', $this->getMethod(), $this->getRequestUri());
	}

	/**
	 * Overrides the PHP global variables according to this request instance
	 *
	 * It overrides $_GET, $_POST, $_REQUEST, $_SERVER, $_COOKIE.
	 * $_FILES is never override, see rfc1867
	 */
	public function overrideGlobals() {
		$_GET     = $this->get->all();
		$_POST    = $this->post->all();
		$_SERVER  = $this->server->all();
		$_COOKIE  = $this->cookies->all();
		$_REQUEST = $this->buildRequestArray($_GET, $_POST, $_COOKIE);

		foreach ($this->headers as $key => $value) {
			$key = strtoupper(str_replace('-', '_', $key));

			if (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
				$_SERVER[$key] = implode(', ', $value);
			}
			else {
				$_SERVER['HTTP_'.$key] = implode(', ', $value);
			}
		}
	}

	/**
	 * Trusts $_SERVER entries coming from proxies
	 *
	 * You should only call this method if your application
	 * is hosted behind a reverse proxy that you manage.
	 *
	 * @param boolean $flag
	 */
	public static function trustProxyData($flag) {
		self::$trustProxy = (boolean) $flag;
	}

	/**
	 * Returns true if $_SERVER entries coming from proxies are trusted,
	 * false otherwise.
	 *
	 * @return boolean
	 */
	public static function isProxyTrusted() {
		return self::$trustProxy;
	}

	/**
	* Normalizes a query string
	*
	* It builds a normalized query string, where keys/value pairs are
	* alphabetized, have consistent escaping and unneeded delimiters are removed.
	*
	* @param  string $qs  query string
	* @return string      a normalized query string for the Request
	*/
	public static function normalizeQueryString($qs) {
		if ('' == $qs) {
			return '';
		}

		$parts = array();
		$order = array();

		foreach (explode('&', $qs) as $param) {
			if ('' === $param || '=' === $param[0]) {
				// Ignore useless delimiters, e.g. "x=y&".
				// Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
				// PHP also does not include them when building _GET.
				continue;
			}

			$keyValuePair = explode('=', $param, 2);

			// GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
			// PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
			// RFC 3986 with rawurlencode.
			$parts[] = isset($keyValuePair[1]) ?
				rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
				rawurlencode(urldecode($keyValuePair[0]));
			$order[] = urldecode($keyValuePair[0]);
		}

		array_multisort($order, SORT_ASC, $parts);

		return implode('&', $parts);
	}

	/**
	 * Returns the client IP address
	 *
	 * @return string  the client IP address
	 */
	public function getClientIp() {
		if (self::$trustProxy) {
			if ($this->server->has('HTTP_CLIENT_IP')) {
				return $this->server->get('HTTP_CLIENT_IP');
			}
			elseif ($this->server->has('HTTP_X_FORWARDED_FOR')) {
				$clientIp = explode(',', $this->server->get('HTTP_X_FORWARDED_FOR'));

				foreach ($clientIp as $ipAddress) {
					$cleanIpAddress = trim($ipAddress);

					if (false !== filter_var($cleanIpAddress, FILTER_VALIDATE_IP)) {
						return $cleanIpAddress;
					}
				}

				return '';
			}
		}

		return $this->server->get('REMOTE_ADDR');
	}

	/**
	 * Returns current script name.
	 *
	 * @return string
	 */
	public function getScriptName() {
		return $this->server->get('SCRIPT_NAME', 'string', $this->server->get('ORIG_SCRIPT_NAME', 'string', ''));
	}

	/**
	 * Gets the request's scheme
	 *
	 * @return string
	 */
	public function getScheme() {
		return $this->isSecure() ? 'https' : 'http';
	}

	/**
	 * Returns the port on which the request is made
	 *
	 * @return int
	 */
	public function getPort() {
		if (self::$trustProxy && $this->headers->has('X-Forwarded-Port')) {
			return (int) $this->headers->get('X-Forwarded-Port', 'int', 80);
		}

		return $this->server->get('SERVER_PORT', 'int', 80);
	}

	/**
	 * Returns the user
	 *
	 * @return string|null
	 */
	public function getUser() {
		return $this->server->get('PHP_AUTH_USER');
	}

	/**
	 * Returns the password
	 *
	 * @return string|null
	 */
	public function getPassword() {
		return $this->server->get('PHP_AUTH_PW');
	}

	/**
	 * Gets the user info
	 *
	 * @return string  a user name and, optionally, scheme-specific information about how to gain authorization to access the server
	 */
	public function getUserInfo() {
		$userinfo = $this->getUser();
		$pass     = $this->getPassword();

		if ('' != $pass) {
			$userinfo .= ":$pass";
		}

		return $userinfo;
	}

	/**
	 * Returns the HTTP host being requested
	 *
	 * The port name will be appended to the host if it's non-standard.
	 *
	 * @return string
	 */
	public function getHttpHost() {
		$scheme = $this->getScheme();
		$port   = $this->getPort();

		if (('http' === $scheme && $port == 80) || ('https' === $scheme && $port == 443)) {
			return $this->getHost();
		}

		return $this->getHost().':'.$port;
	}

	/**
	 * Returns the requested URI
	 *
	 * @return string  the raw URI (i.e. not urldecoded)
	 */
	public function getRequestUri() {
		if (null === $this->requestUri) {
			$this->requestUri = $this->prepareRequestUri();
		}

		return $this->requestUri;
	}

	/**
	 * Generates the normalized query string for the Request
	 *
	 * It builds a normalized query string, where keys/value pairs are
	 * alphabetized and have consistent escaping.
	 *
	 * @return string|null  a normalized query string for the Request
	 */
	public function getQueryString() {
		$qs = self::normalizeQueryString($this->server->get('QUERY_STRING', 'string', ''));

		return '' === $qs ? null : $qs;
	}

	/**
	 * Checks whether the request is secure or not
	 *
	 * @return boolean
	 */
	public function isSecure() {
		return (
			(strtolower($this->server->get('HTTPS')) === 'on' || $this->server->get('HTTPS') == 1)
			||
			(self::$trustProxy && strtolower($this->headers->get('SSL-HTTPS')) == 'on' || $this->headers->get('SSL-HTTPS') == 1)
			||
			(self::$trustProxy && strtolower($this->headers->get('X-Forwarded-Proto')) == 'https')
		);
	}

	/**
	 * Returns the host name
	 *
	 * @return string
	 */
	public function getHost() {
		if (self::$trustProxy && $host = $this->headers->get('X-Forwarded-Host')) {
			$elements = explode(',', $host);
			$host     = trim($elements[count($elements) - 1]);
		}
		else {
			if (!$host = $this->headers->get('Host')) {
				$server = $this->server;

				if (!$host = $server->get('SERVER_NAME')) {
					if (!$host = $server->get('SERVER_ADDR')) {
						// return a well defined value if run on CLI to make unit tests possible
						$host = PHP_SAPI;
					}
				}
			}
		}

		// Remove port number from host
		$host = preg_replace('/:\d+$/', '', $host);

		// host is lowercase as per RFC 952/2181
		return trim(strtolower($host));
	}

	/**
	 * Gets the request method
	 *
	 * The method is always an uppercased string.
	 *
	 * @return string  the request method
	 */
	public function getMethod() {
		if (null === $this->method) {
			$this->method = strtoupper($this->server->get('REQUEST_METHOD', 'string', 'GET'));

			if ('POST' === $this->method) {
				$this->method = strtoupper($this->headers->get('X-HTTP-Method-Override', 'string', 'POST'));
			}
		}

		return $this->method;
	}

	/**
	 * Get the user agent
	 *
	 * @return string|null  the user agent string (null if no UA is present)
	 */
	public function getUserAgent() {
		return $this->headers->get('User-Agent');
	}

	/**
	 * Gets the format associated with the request
	 *
	 * @return string|null  the format (null if no content type is present)
	 */
	public function getContentType() {
		return $this->headers->get('Content-Type');
	}

	/**
	 * Checks if the request method is of specified type
	 *
	 * @param  string $method  uppercase request method (GET, POST etc).
	 * @return boolean
	 */
	public function isMethod($method) {
		return $this->getMethod() === strtoupper($method);
	}

	/**
	 * Returns the request body content
	 *
	 * @param  boolean $asResource  if true, a resource will be returned
	 * @return string|resource      the request body content or a resource to read the body stream.
	 */
	public function getContent($asResource = false) {
		if (false === $this->content || (true === $asResource && null !== $this->content)) {
			throw new LogicException('getContent() can only be called once when using the resource return type.');
		}

		if (true === $asResource) {
			$this->content = false;
			return fopen('php://input', 'rb');
		}

		if (null === $this->content) {
			$this->content = file_get_contents('php://input');
		}

		return $this->content;
	}

	/**
	 * Gets the Etags
	 *
	 * @return array  the entity tags
	 */
	public function getETags() {
		return preg_split('/\s*,\s*/', $this->headers->get('If-None-Match'), null, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Returns the preferred language
	 *
	 * @param  array $locales  an array of ordered available locales
	 * @return string|null     the preferred locale
	 */
	public function getPreferredLanguage(array $locales = null) {
		$preferredLanguages = $this->getLanguages();

		if (empty($locales)) {
			return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null;
		}

		if (!$preferredLanguages) {
			return $locales[0];
		}

		$preferredLanguages = array_values(array_intersect($preferredLanguages, $locales));

		return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $locales[0];
	}

	/**
	 * Gets a list of languages acceptable by the client browser.
	 *
	 * @return array  languages ordered in the user browser preferences
	 */
	public function getLanguages() {
		if (null !== $this->languages) {
			return $this->languages;
		}

		$languages       = $this->splitHttpAcceptHeader($this->headers->get('Accept-Language'));
		$this->languages = array();

		foreach ($languages as $lang => $q) {
			if (strstr($lang, '-')) {
				$codes = explode('-', $lang);

				if ($codes[0] == 'i') {
					// Language not listed in ISO 639 that are not variants
					// of any listed language, which can be registered with the
					// i-prefix, such as i-cherokee
					if (count($codes) > 1) {
						$lang = $codes[1];
					}
				}
				else {
					for ($i = 0, $max = count($codes); $i < $max; $i++) {
						if ($i == 0) {
							$lang = strtolower($codes[0]);
						}
						else {
							$lang .= '_'.strtoupper($codes[$i]);
						}
					}
				}
			}

			$this->languages[] = $lang;
		}

		return $this->languages;
	}

	/**
	 * Gets a list of charsets acceptable by the client browser
	 *
	 * @return array  list of charsets in preferable order
	 */
	public function getCharsets() {
		if (null !== $this->charsets) {
			return $this->charsets;
		}

		return $this->charsets = array_keys($this->splitHttpAcceptHeader($this->headers->get('Accept-Charset')));
	}

	/**
	 * Gets a list of content types acceptable by the client browser
	 *
	 * @return array  list of content types in preferable order
	 */
	public function getAcceptableContentTypes() {
		if (null !== $this->acceptableContentTypes) {
			return $this->acceptableContentTypes;
		}

		return $this->acceptableContentTypes = array_keys($this->splitHttpAcceptHeader($this->headers->get('Accept')));
	}

	/**
	 * Returns true if the request is a XMLHttpRequest
	 *
	 * @return boolean  true if the request is an XMLHttpRequest, false otherwise
	 */
	public function isAjax() {
		return 'XMLHttpRequest' === $this->headers->get('X-Requested-With');
	}

	/**
	 * Splits an Accept-* HTTP header
	 *
	 * @param  string $header  header to split
	 * @return array           array indexed by the values of the Accept-* header in preferred order
	 */
	public function splitHttpAcceptHeader($header) {
		if (!$header) {
			return array();
		}

		$values = array();

		foreach (array_filter(explode(',', $header)) as $value) {
			// Cut off any q-value that might come after a semi-colon
			if (preg_match('/;\s*(q=.*$)/', $value, $match)) {
				$q     = (float) substr(trim($match[1]), 2);
				$value = trim(substr($value, 0, -strlen($match[0])));
			}
			else {
				$q = 1;
			}

			if (0 < $q) {
				$values[trim($value)] = $q;
			}
		}

		arsort($values);
		reset($values);

		return $values;
	}

	/**
	 * Gets the HTTP headers
	 *
	 * @return sly_Util_ArrayObject
	 */
	protected function getHeadersFromServer(sly_Util_ArrayObject $server) {
		$headers = array();

		foreach ($server as $key => $value) {
			if (0 === strpos($key, 'HTTP_')) {
				$headers[substr($key, 5)] = $value;
			}
			// CONTENT_* are not prefixed with HTTP_
			elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
				$headers[$key] = $value;
			}
		}

		return new sly_Util_ArrayObject($headers, sly_Util_ArrayObject::NORMALIZE_HTTP_HEADER);
	}

	/*
	 * The following method is derived from code of the Zend Framework (1.10dev - 2010-01-24)
	 * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
	 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
	 */

	/**
	 * Prepare the request URI
	 *
	 * @return string  the request URI
	 */
	protected function prepareRequestUri() {
		$requestUri = '';

		if ($this->headers->has('X-Rewrite-Url') && false !== stripos(PHP_OS, 'WIN')) {
			// check this first so IIS will catch
			$requestUri = $this->headers->get('X-Rewrite-Url');
		}
		elseif ($this->server->get('IIS_WasUrlRewritten') == '1' && $this->server->get('UNENCODED_URL') != '') {
			// IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
			$requestUri = $this->server->get('UNENCODED_URL');
		}
		elseif ($this->server->has('REQUEST_URI')) {
			$requestUri = $this->server->get('REQUEST_URI');
		}
		elseif ($this->server->has('ORIG_PATH_INFO')) {
			// IIS 5.0, PHP as CGI
			$requestUri = $this->server->get('ORIG_PATH_INFO');

			if ('' != $this->server->get('QUERY_STRING')) {
				$requestUri .= '?'.$this->server->get('QUERY_STRING');
			}
		}

		return $requestUri;
	}

	public function overrideGet($get) {
		$this->get = new sly_Util_ArrayObject($get);
	}
}
