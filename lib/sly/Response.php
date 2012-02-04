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
 * HTTP response
 *
 * This is basically a stripped down version of Symfony2's Response class,
 * taking away the PHP 5.3 stuff and replacing the header bag with a simple
 * array.
 */
class sly_Response {
	protected $headers;
	protected $content;
	protected $statusCode;
	protected $statusText;
	protected $charset;

	static public $statusTexts = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
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
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);

	/**
	 * Constructor
	 *
	 * @param string  $content The response content
	 * @param integer $status  The response status code
	 * @param array   $headers An array of response headers
	 */
	public function __construct($content = '', $status = 200, array $headers = array()) {
		$this->headers = new sly_Util_Array($headers);
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

	public function setContentType($type, $charset = null) {
		$this->headers->set('content-type', $type);
		if ($charset !== null) $this->setCharset($charset);
	}

	public function setHeader($name, $value) {
		$this->headers->set(mb_strtolower($name), $value);
	}

	public function hasHeader($name) {
		$this->headers->has(mb_strtolower($name));
	}

	public function getHeader($name, $default = null) {
		$this->headers->get(mb_strtolower($name), $default);
	}

	public function removeHeader($name) {
		$this->headers->remove(mb_strtolower($name));
	}

	/**
	 * Prepares the Response before it is sent to the client
	 *
	 * This method tweaks the Response to ensure that it is compliant with
	 * RFC 2616.
	 */
	public function prepare() {
		if ($this->isInformational() || in_array($this->statusCode, array(204, 304))) {
			$this->setContent('');
		}

		// Fix Content-Type
		$charset = $this->charset ? $this->charset : 'UTF-8';

		if ($this->headers->has('content-type') && false === strpos($this->headers->get('content-type'), 'charset')) {
			// add the charset
			$this->headers->set('content-type', $this->headers->get('content-type').'; charset='.$charset);
		}

		// Fix Content-Length
		if ($this->headers->has('transfer-encoding')) {
			$this->headers->remove('content-length');
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
		foreach ($this->headers->get('') as $name => $value) {
			// 'content-length' => 'Content-Length'
			$name = implode('-', array_map('ucfirst', explode('-', $name)));
			header($name.': '.$value, false);
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
	 */
	public function send() {
		// give listeners a very last chance to tamper with this response
		sly_Core::dispatcher()->notify('SLY_SEND_RESPONSE', $this);

		// safely enable gzip output
		if (!sly_ini_get('zlib.output_compression')) {
			if (ob_start('ob_gzhandler') === false) {
				// manually send content length if everything fails
				$this->setHeader('Content-Length', mb_strlen($this->content, '8bit'));
			}
		}

		// RFC 2616 said every not explicitly keep-alive Connection should receice a Connection: close,
		// but at least Apache Websever breaks this, if the client sends just nothing (which is also not compliant).
		if (empty($_SERVER['HTTP_CONNECTION']) || strtolower($_SERVER['HTTP_CONNECTION']) !== 'keep-alive') {
			$this->setHeader('Connection', 'close');
		}

		$this->sendHeaders();
		$this->sendContent();

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}

	/**
	 * Sets the response content
	 *
	 * Valid types are strings, numbers, and objects that implement a
	 * __toString() method.
	 *
	 * @param mixed  $content
	 */
	public function setContent($content) {
		if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
			throw new UnexpectedValueException('The Response content must be a string or object implementing __toString(), "'.gettype($content).'" given.');
		}

		$this->content = (string) $content;
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
	 */
	public function setStatusCode($code, $text = null) {
		$this->statusCode = (int) $code;

		if ($this->isInvalid()) {
			throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
		}

		$this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
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
		return $this->headers->getDate('date');
	}

	/**
	 * Sets the Date header
	 *
	 * @param int $date  the date as a timestamp
	 */
	public function setDate($date) {
		$this->headers->set('date', date('D, d M Y H:i:s', $date).' GMT');
	}

	/**
	 * Returns the value of the Expires header
	 *
	 * @return string  the expire time as a string
	 */
	public function getExpires() {
		return $this->headers->getDate('expires');
	}

	/**
	 * Sets the Expires HTTP header
	 *
	 * If passed a null value, it removes the header.
	 *
	 * @param int $date  the date as a timestamp
	 */
	public function setExpires($date = null) {
		if (null === $date) {
			$this->headers->remove('expires');
		}
		else {
			$this->headers->set('expires', date('D, d M Y H:i:s', $date).' GMT');
		}
	}

	/**
	 * Returns the Last-Modified HTTP header
	 *
	 * @return string  the last modified time as a string
	 */
	public function getLastModified() {
		return $this->headers->remove('last-modified');
	}

	/**
	 * Sets the Last-Modified HTTP header
	 *
	 * If passed a null value, it removes the header.
	 *
	 * @param int $date  the date as a timestamp
	 */
	public function setLastModified($date = null) {
		if (null === $date) {
			$this->headers->remove('last-modified');
		}
		else {
			$this->headers->set('last-modified', date('D, d M Y H:i:s', $date).' GMT');
		}
	}

	/**
	 * Returns the literal value of ETag HTTP header
	 *
	 * @return string  the ETag HTTP header
	 */
	public function getEtag() {
		return $this->headers->get('etag');
	}

	/**
	 * Sets the ETag value.
	 *
	 * @param string  $etag  the ETag unique identifier
	 * @param boolean $weak  whether you want a weak ETag or not
	 */
	public function setEtag($etag = null, $weak = false) {
		if (null === $etag) {
			$this->headers->remove('etag');
		}
		else {
			if (0 !== strpos($etag, '"')) {
				$etag = '"'.$etag.'"';
			}

			$this->headers->set('etag', (true === $weak ? 'W/' : '').$etag);
		}
	}

	/**
	 * Sets Response cache headers (validation and/or expiration).
	 *
	 * Available options are etag and last_modified.
	 *
	 * @param array $options  an array of cache options
	 */
	public function setCache(array $options) {
		if ($diff = array_diff(array_keys($options), array('etag', 'last_modified'))) {
			throw new InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', array_keys($diff))));
		}

		if (isset($options['etag'])) {
			$this->setEtag($options['etag']);
		}

		if (isset($options['last_modified'])) {
			$this->setLastModified($options['last_modified']);
		}
	}

	/**
	 * Modifies the response so that it conforms to the rules defined for a 304 status code.
	 *
	 * This sets the status, removes the body, and discards any headers that MUST
	 * NOT be included in 304 responses.
	 *
	 * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
	 */
	public function setNotModified() {
		$this->setStatusCode(304);
		$this->setContent(null);

		// remove headers that MUST NOT be included with 304 Not Modified responses
		foreach (array('allow', 'content-encoding', 'content-language', 'content-length', 'content-md5', 'content-type', 'last-modified') as $header) {
			$this->headers->remove($header);
		}
	}

	/**
	 * Determines if the Response validators (ETag, Last-Modified) matches
	 * a conditional value specified in the Request.
	 *
	 * If the Response is not modified, it sets the status code to 304 and
	 * remove the actual content by calling the setNotModified() method.
	 *
	 * @return boolean  true if not modified, else false
	 */
	public function isNotModified() {
		$notModified  = false;
		$selfModified = $this->headers->get('last-modified');
		$selfModified = $selfModified ? strtotime($this->headers->get('last-modified')) : null;
		$lastModified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null;
		$eTags        = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? isset($_SERVER['HTTP_IF_NONE_MATCH']) : '';
		$eTags        = preg_split('/\s*,\s*/', $eTags, null, PREG_SPLIT_NO_EMPTY);

		if ($eTags) {
			$notModified =
				(in_array($this->getEtag(), $eTags, true) || in_array('*', $eTags)) &&
				(!$lastModified || $selfModified === $lastModified);
		}
		elseif ($lastModified) {
			$notModified = $lastModified === $selfModified;
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
		return in_array($this->statusCode, array(201, 301, 302, 303, 307)) && (null === $location ? true : ($location == $this->headers->get('location')));
	}
}
