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
 * @ingroup util
 */
class sly_Util_HTTP {
	/**
	 * @param mixed  $target      URL, article ID or article model
	 * @param array  $parameters
	 * @param string $noticeText
	 * @param int    $status
	 */
	public static function redirect($target, $parameters = array(), $noticeText = '', $status = 301) {
		$targetUrl = '';

		if ($target instanceof sly_Model_Article || sly_Util_String::isInteger($target)) {
			$clang     = $target instanceof sly_Model_Article ? $target->getCLang() : sly_Core::getCurrentClang();
			$targetUrl = self::getAbsoluteUrl($target, $clang, $parameters, '&');
		}
		else {
			$targetUrl = $target;
		}

		if (empty($noticeText)) {
			$noticeText = t('redirect_to', sly_html($targetUrl));
		}

		$stati = array(
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect'
		);

		$status = isset($stati[$status]) ? $status : 301;
		$text   = $stati[$status];

		while (ob_get_level()) ob_end_clean();
		header('HTTP/1.0 '.$status.' '.$text);
		header('Location: '.$targetUrl);
		exit($noticeText);
	}

	/**
	 * @param  mixed   $targetArticle  article ID or article model
	 * @param  int     $clang
	 * @param  array   $parameters
	 * @param  string  $divider
	 * @param  boolean $secure
	 * @return string
	 */
	public static function getAbsoluteUrl($targetArticle, $clang = null, $parameters = array(), $divider = '&amp;', $secure = null) {
		$url = self::getUrl($targetArticle, $clang, $parameters, $divider, null);

		// did we already get an absolute URL (cross domain realurl implementations)?
		if (!preg_match('#^[a-z]+://#', $url)) {
			if ($url[0] === '/') {
				$url = self::getBaseUrl(false).$url;
			}
			else {
				$url = self::getBaseUrl(true).'/'.$url;
			}
		}

		// remove all '/./' steps
		$url = preg_replace('#/\./#', '/', $url);

		// switch between http and https?
		if ($secure !== null) {
			$url = preg_replace('#^https?://#', $secure ? 'https://' : 'http://', $url);
		}

		return $url;
	}

	/**
	 * @param  mixed   $targetArticle  article ID or article model
	 * @param  int     $clang
	 * @param  array   $parameters
	 * @param  string  $divider
	 * @param  boolean $secure
	 * @return string
	 */
	public static function getUrl($targetArticle, $clang = null, $parameters = array(), $divider = '&amp;', $secure = null) {
		$switch = $secure !== null && $secure !== self::isSecure();

		if ($switch) {
			return self::getAbsoluteUrl($targetArticle, $clang, $parameters, $divider, $secure);
		}

		$articleID = self::resolveArticle($targetArticle);
		$article   = sly_Util_Article::findById($articleID, $clang);

		return $article->getUrl($parameters, $divider);
	}

	/**
	 * @param  boolean $addScriptPath
	 * @return string
	 */
	public static function getBaseUrl($addScriptPath = false, $forceProtocol = null) {
		$protocol = $forceProtocol === null ? (self::isSecure() ? 'https': 'http') : $forceProtocol;
		$host     = self::getHost();
		$path     = '';

		if ($addScriptPath) {
			// in CLI, the SCRIPT_NAME would be something like 'C:\xamp\php\phpunit'...
			if (PHP_SAPI === 'cli') {
				$path = '/sally';
			}
			else {
				$path = dirname($_SERVER['SCRIPT_NAME']); // '/foo' or '/foo/sally/backend'

				if (IS_SALLY_BACKEND) {
					$path = dirname(dirname($path));
				}

				$path = str_replace('\\', '/', $path);
			}
		}

		return rtrim(sprintf('%s://%s%s', $protocol, $host, $path), '/');
	}

	/**
	 * Ermitteln einer Artikel-ID
	 *
	 * Diese Methode ermittelt zu einem Artikel die dazugehörige ID.
	 *
	 * @param  mixed $article  sly_Model_Article oder int
	 * @return int             die ID oder -1 falls keine gefunden wurde
	 */
	protected static function resolveArticle($article) {
		if (sly_Util_String::isInteger($article)) return (int) $article;
		if ($article instanceof sly_Model_Article) return (int) $article->getId();

		return -1;
	}

	/**
	 * Baut einen Parameter String anhand des array $params
	 *
	 * @param  mixed  $params   array or string
	 * @param  string $divider  only used if $params is array
	 * @return string           the host or an empty string if none found
	 */
	public static function queryString($params, $divider = '&amp;') {
		if (!empty($params)) {
			if (is_array($params)) {
				return $divider.http_build_query($params, '', $divider);
			}
			else {
				return $params;
			}
		}
	}

	/**
	 * @return string
	 */
	public static function getHost() {
		// return a well defined value if run on CLI to make unit tests possible
		if (PHP_SAPI === 'cli') return 'cli';

		$host = '';

		if     (isset($_SERVER['HTTP_X_FORWARDED_HOST']))   $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
		elseif (isset($_SERVER['HTTP_HOST']))               $host = $_SERVER['HTTP_HOST'];
		elseif (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) $host = $_SERVER['HTTP_X_FORWARDED_SERVER'];
		elseif (isset($_SERVER['SERVER_NAME']))             $host = $_SERVER['SERVER_NAME'];

		// remove port if present
		if ($host && strpos($host, ':') !== false) {
			$host = substr($host, 0, strpos($host, ':'));
		}

		return $host;
	}

	/**
	 * @return boolean
	 */
	public static function isSecure() {
		return
			(isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
	}
}
