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
 * @ingroup util
 */
class sly_Util_HTTP {
	/**
	 * @param mixed  $target      URL, article ID or article model
	 * @param array  $parameters
	 * @param string $noticeText
	 */
	public static function tempRedirect($target, $parameters = array(), $noticeText = '') {
		self::redirect($target, $parameters, $noticeText, 302);
	}

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

		if (empty($targetUrl)) {
			$noticeText = t('redirect_notfound');
		}

		if (empty($noticeText)) {
			$noticeText = t('redirect_to', sly_html($targetUrl));
		}

		$stati = array(
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
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
	 * Get the absolute base URL to the project's root (frontend)
	 *
	 * @param  boolean $addScriptPath
	 * @param  mixed   $forceProtocol  a concrete protocol like 'http' or null for the current one
	 * @param  mixed   $forcePort      a concrete port or null for the current one
	 * @return string
	 */
	public static function getBaseUrl($addScriptPath = false, $forceProtocol = null, $forcePort = null) {
		return sly_Core::getRequest()->getBaseUrl($addScriptPath, $forceProtocol, $forcePort);
	}

	/**
	 * @return string
	 */
	public static function getBasePath() {
		return sly_Core::getRequest()->getBasePath();
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
	public static function queryString($params, $divider = '&amp;', $prependDivider = true) {
		if (!empty($params)) {
			if (is_array($params)) {
				return ($prependDivider ? $divider : '').http_build_query($params, '', $divider);
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
		return sly_Core::getRequest()->getHost();
	}

	/**
	 * @return int
	 */
	public static function getPort() {
		return sly_Core::getRequest()->getPort();
	}

	/**
	 * @return boolean
	 */
	public static function isSecure() {
		return sly_Core::getRequest()->isSecure();
	}
}
