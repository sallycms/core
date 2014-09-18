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
 * HTTP response
 *
 * This is basically a stripped down version of Symfony2's Response class,
 * taking away the PHP 5.3 stuff and replacing the header bag with a simple
 * array.
 */
class sly_Response {
	protected $headers;
	protected $cacheControl;
	protected $content;
	protected $statusCode;
	protected $statusText;
	protected $charset;

	/**
	 * Status codes translation table.
	 *
	 * The list of codes is complete according to the
	 * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
	 * (last updated 2012-02-13).
	 *
	 * Unless otherwise noted, the status code is defined in RFC2616.
	 *
	 * @var array
	 */
	public static $statusTexts = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',            // RFC2518
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',          // RFC4918
		208 => 'Already Reported',      // RFC5842
		226 => 'IM Used',               // RFC3229
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Reserved',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',    // RFC-reschke-http-status-308-07
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',  // RFC4918
		423 => 'Locked',                // RFC4918
		424 => 'Failed Dependency',     // RFC4918
		425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
		426 => 'Upgrade Required',      // RFC2817
		428 => 'Precondition Required', // RFC-nottingham-http-new-status-04
		429 => 'Too Many Requests',     // RFC-nottingham-http-new-status-04
		431 => 'Request Header Fields Too Large',   // RFC-nottingham-http-new-status-04
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates (Experimental)', // [RFC2295]
		507 => 'Insufficient Storage',  // RFC4918
		508 => 'Loop Detected',         // RFC5842
		510 => 'Not Extended',          // RFC2774
		511 => 'Network Authentication Required'   // RFC-nottingham-http-new-status-04
	);

	/**
	 * Constructor
	 *
	 * @param string  $content  The response content
	 * @param integer $status   The response status code
	 * @param array   $headers  An array of response headers
	 */
	public function __construct($content = '', $status = 200, array $headers = array()) {
		$this->headers      = new sly_Util_ArrayObject(array(), sly_Util_ArrayObject::NORMALIZE_HTTP_HEADER);
		$this->cacheControl = array();

		foreach ($headers as $name => $values) {
			$this->setHeader($name, $values);
		}

		$this->setContent($content);
		$this->setStatusCode($status);
	}

	/**
	 * Returns the response content as it will be sent (with the headers)
	 *
	 * @return string The response content
	 */
	public function __toString() {
		$this->prepare();

		return
			sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->statusText)."\r\n".
			$this->headers."\r\n".
			$this->getContent();
	}

	/**
	 *
	 * @param string $type
	 * @param strinf $charset
	 * @return sly_Response
	 */
	public function setContentType($type, $charset = null) {
		if ($charset !== null) {
			$this->setCharset($charset);
			$type .= '; charset='.$charset;
		}

		$this->setHeader('content-type', $type);

		return $this;
	}

	/**
	 * Set or adds a header value
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  boolean $replace
	 * @return sly_Response
	 */
	public function setHeader($name, $values, $replace = true) {
		$name   = strtr(strtolower($name), '_', '-');
		$values = array_values((array) $values);

		if (true === $replace || !$this->headers->has($name)) {
			$this->headers->set($name, $values);
		}
		else {
			$this->headers->set($name, array_merge($this->headers->get($name), $values));
		}

		if ('cache-control' === $name) {
			$this->cacheControl = $this->parseCacheControl($values[0]);
		}

		return $this;
	}

	public function hasHeader($name) {
		$name = strtr(strtolower($name), '_', '-');
		return $this->headers->has($name);
	}

	public function getHeader($name, $default = null, $first = true) {
		if (!$this->headers->has($name)) {
			if (null === $default) {
				return $first ? null : array();
			}

			return $first ? $default : array($default);
		}

		$values = $this->headers->get($name);

		if ($first) {
			return count($values) ? $values[0] : $default;
		}

		return $values;
	}

	/**
	 *
	 * @param string $name
	 * @return sly_Response
	 */
	public function removeHeader($name) {
		$name = strtr(strtolower($name), '_', '-');

		$this->headers->remove($name);

		if ('cache-control' === $name) {
			$this->cacheControl = array();
		}

		return $this;
	}

	/**
	 * Prepares the Response before it is sent to the client
	 *
	 * This method tweaks the Response to ensure that it is compliant with
	 * RFC 2616.
	 */
	public function prepare() {
		if ($this->isInformational() || in_array($this->statusCode, array(204, 304))) {
			$this->setContent(null);
		}

		// Fix Content-Type
		$charset = $this->charset ? $this->charset : 'UTF-8';

		if ($this->headers->has('Content-Type')) {
			$type = $this->getHeader('Content-Type');

			if ((0 === strpos($type, 'text/') || $type === 'application/javascript') && false === strpos($type, 'charset')) {
				// add the charset
				$this->setHeader('Content-Type', $type.'; charset='.$charset);
			}
		}

		// Fix Content-Length
		if ($this->headers->has('Transfer-Encoding')) {
			$this->removeHeader('Content-Length');
		}
	}

	/**
	 * Sends HTTP headers
	 */
	public function sendHeaders() {
		// headers have already been sent by the developer
		if (headers_sent()) return;

		$this->prepare();

		// status
		header(sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->statusText));

		// headers
		foreach ($this->headers as $name => $values) {
			$name = implode('-', array_map('ucfirst', explode('-', $name)));

			foreach ($values as $value) {
				header($name.': '.$value, false);
			}
		}

		// cookies
//		foreach ($this->headers->getCookies() as $cookie) {
//			setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
//		}
	}

	/**
	 * Sends content for the current web response
	 */
	public function sendContent() {
		print $this->content;
	}

	/**
	 * Sends HTTP headers and content
	 *
	 * @param sly_Request           $request     the request that is responded to (used to determine keep-alive status)
	 * @param sly_Event_IDispatcher $dispatcher  the dispatcher to use to notify on SLY_SEND_RESPONSE
	 */
	public function send(sly_Request $request = null, sly_Event_IDispatcher $dispatcher = null) {
		// give listeners a very last chance to tamper with this response
		if (!$dispatcher) $dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_SEND_RESPONSE', $this);

		// safely enable gzip output
		$this->enableGzip();

		if (!$request) {
			$request = sly_Core::getRequest();
		}

		// RFC 2616 said every not explicitly keep-alive Connection should receive a Connection: close,
		// but at least Apache Websever breaks this, if the client sends just nothing (which is also not compliant).
		if ($request->header('Connection') !== 'keep-alive') {
			$this->setHeader('Connection', 'close');
		}

		$this->sendHeaders();
		$this->sendContent();

		// maybe someone wants to do some cleanup after response is sent
		$dispatcher->notify('SLY_RESPONSE_SENT', $this);

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}

	protected function enableGzip() {
		// safely enable gzip output
		if (!sly_ini_get('zlib.output_compression')) {
			if (@ob_start('ob_gzhandler') === false) {
				// manually send content length if everything fails and we're not streaming
				if ($this->content !== null) {
					$this->setHeader('Content-Length', mb_strlen($this->content, '8bit'));
				}
			}
		}
	}

	/**
	 * Sets the response content
	 *
	 * Valid types are strings, numbers, and objects that implement a
	 * __toString() method.
	 *
	 * @param mixed  $content
	 * @return sly_Response
	 */
	public function setContent($content) {
		if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
			throw new UnexpectedValueException('The Response content must be a string or object implementing __toString(), "'.gettype($content).'" given.');
		}

		$this->content = (string) $content;
		return $this;
	}

	/**
	 * Gets the current response content
	 *
	 * @return string  the content
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Sets response status code
	 *
	 * @throws InvalidArgumentException  when the HTTP status code is not valid
	 * @param  integer $code             HTTP status code
	 * @param  string  $text             HTTP status text
	 * @return sly_Response
	 */
	public function setStatusCode($code, $text = null) {
		$this->statusCode = (int) $code;

		if ($this->isInvalid()) {
			throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
		}

		$this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
		return $this;
	}

	/**
	 * Retrieves status code for the current web response.
	 *
	 * @return int  status code
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * Sets response charset.
	 *
	 * @param string $charset  character set
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
	}

	/**
	 * Retrieves the response charset
	 *
	 * @return string  character set
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Returns the Date header
	 *
	 * @return string  the date as a string
	 */
	public function getDate() {
		return $this->getHeader('date');
	}

	/**
	 * Sets the Date header
	 *
	 * @param  int $date  the date as a timestamp
	 * @return sly_Response
	 */
	public function setDate($date) {
		$this->setHeader('Date', gmdate('D, d M Y H:i:s', $date).' GMT');
		return $this;
	}

	/**
	 * Returns the value of the Expires header
	 *
	 * @return string  the expire time as a string
	 */
	public function getExpires() {
		return $this->getHeader('Expires');
	}

	/**
	 * Sets the Expires HTTP header
	 *
	 * If passed a null value, it removes the header.
	 *
	 * @param  int $date  the date as a timestamp
	 * @return sly_Response
	 */
	public function setExpires($date = null) {
		if (null === $date) {
			$this->removeHeader('Expires');
		}
		else {
			$this->setHeader('Expires', gmdate('D, d M Y H:i:s', $date).' GMT');
		}
		return $this;
	}

	/**
	 * Returns the Last-Modified HTTP header
	 *
	 * @return string  the last modified time as a string
	 */
	public function getLastModified() {
		return $this->getHeader('Last-Modified');
	}

	/**
	 * Sets the Last-Modified HTTP header
	 *
	 * If passed a null value, it removes the header.
	 *
	 * @param  int $date  the date as a timestamp
	 * @return sly_Response
	 */
	public function setLastModified($date = null) {
		if (null === $date) {
			$this->removeHeader('Last-Modified');
		}
		else {
			$this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $date).' GMT');
		}
		return $this;
	}

	/**
	 * Returns the literal value of ETag HTTP header
	 *
	 * @return string  the ETag HTTP header
	 */
	public function getEtag() {
		return $this->getHeader('ETag');
	}

	/**
	 * Sets the ETag value.
	 *
	 * @param string  $etag  the ETag unique identifier
	 * @param boolean $weak  whether you want a weak ETag or not
	 * @return sly_Response
	 */
	public function setEtag($etag = null, $weak = false) {
		if (null === $etag) {
			$this->removeHeader('ETag');
		}
		else {
			if (0 !== strpos($etag, '"')) {
				$etag = '"'.$etag.'"';
			}

			$this->setHeader('ETag', (true === $weak ? 'W/' : '').$etag);
		}
		return $this;
	}

	/**
	 * Sets Response cache headers (validation and/or expiration).
	 *
	 * Available options are etag and last_modified.
	 *
	 * @param array $options  an array of cache options
	 * @return sly_Response
	 */
	public function setCache(array $options) {
		if ($diff = array_diff(array_keys($options), array('etag', 'last_modified'))) {
			throw new InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', array_values($diff))));
		}

		if (isset($options['etag'])) {
			$this->setEtag($options['etag']);
		}

		if (isset($options['last_modified'])) {
			$this->setLastModified($options['last_modified']);
		}
		return $this;
	}

	/**
	 * Modifies the response so that it conforms to the rules defined for a 304 status code.
	 *
	 * This sets the status, removes the body, and discards any headers that MUST
	 * NOT be included in 304 responses.
	 *
	 * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
	 * @return sly_Response
	 */
	public function setNotModified() {
		$this->setStatusCode(304);
		$this->setContent(null);

		// remove headers that MUST NOT be included with 304 Not Modified responses
		foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
			$this->removeHeader($header);
		}
		return $this;
	}

	/**
	 * Determines if the Response validators (ETag, Last-Modified) matches
	 * a conditional value specified in the Request.
	 *
	 * If the Response is not modified, it sets the status code to 304 and
	 * remove the actual content by calling the setNotModified() method.
	 *
	 * @param  sly_Request $request  the request to work on
	 * @return boolean               true if not modified, else false
	 */
	public function isNotModified(sly_Request $request = null) {
		if (!$request) {
			$request = sly_Core::getRequest();
		}

		$notModified = false;
		$etags       = $request->getEtags();
		$localMod    = $this->getHeader('last-modified');
		$remoteMod   = $request->headers->get('if-modified-since');
		$localMod    = $localMod  ? strtotime($localMod) : null;
		$remoteMod   = $remoteMod ? @strtotime($remoteMod) : null;

		if ($etags) {
			$notModified =
				(in_array($this->getEtag(), $etags) || in_array('*', $etags)) &&
				(!$remoteMod || $localMod === $remoteMod);
		}
		elseif ($remoteMod) {
			$notModified = $remoteMod === $localMod;
		}

		if ($notModified) {
			$this->setNotModified();
		}

		return $notModified;
	}

	public function isInvalid()       { return $this->statusCode < 100 || $this->statusCode >= 600; }
	public function isInformational() { return $this->statusCode >= 100 && $this->statusCode < 200; }
	public function isSuccessful()    { return $this->statusCode >= 200 && $this->statusCode < 300; }
	public function isRedirection()   { return $this->statusCode >= 300 && $this->statusCode < 400; }
	public function isClientError()   { return $this->statusCode >= 400 && $this->statusCode < 500; }
	public function isServerError()   { return $this->statusCode >= 500 && $this->statusCode < 600; }

	public function isOk()        { return 200 === $this->statusCode; }
	public function isForbidden() { return 403 === $this->statusCode; }
	public function isNotFound()  { return 404 === $this->statusCode; }

	public function isEmpty() {
		return in_array($this->statusCode, array(201, 204, 304));
	}

	public function isRedirect($location = null) {
		return in_array($this->statusCode, array(201, 301, 302, 303, 307, 308)) && (null === $location ? true : ($location == $this->headers->get('location')));
	}

	public function addCacheControlDirective($key, $value = true) {
		$this->cacheControl[$key] = $value;

		$this->setHeader('Cache-Control', $this->getCacheControlHeader());
	}

	public function hasCacheControlDirective($key) {
		return array_key_exists($key, $this->cacheControl);
	}

	public function getCacheControlDirective($key) {
		return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
	}

	public function removeCacheControlDirective($key) {
		unset($this->cacheControl[$key]);

		$this->setHeader('Cache-Control', $this->getCacheControlHeader());
	}

	protected function getCacheControlHeader() {
		$parts = array();
		ksort($this->cacheControl);

		foreach ($this->cacheControl as $key => $value) {
			if (true === $value) {
				$parts[] = $key;
			}
			else {
				if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
					$value = '"' . $value . '"';
				}

				$parts[] = "$key=$value";
			}
		}

		return implode(', ', $parts);
	}

	/**
	 * Parses a Cache-Control HTTP header
	 *
	 * @param  string $header the value of the Cache-Control HTTP header
	 * @return array          an array representing the attribute values
	 */
	protected function parseCacheControl($header) {
		$cacheControl = array();

		preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
		}

		return $cacheControl;
	}
}
