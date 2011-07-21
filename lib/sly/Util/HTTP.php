<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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

		$stati  = array(301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other');
		$status = isset($stati[$status]) ? $status : 301;
		$text   = $stati[$status];

		while (ob_get_level()) ob_end_clean();
		header('HTTP/1.0 '.$status.' '.$text);
		header('Location: '.$targetUrl);
		exit($noticeText);
	}

	public static function getAbsoluteUrl($targetArticle, $clang = false, $parameters = array(), $divider = '&amp;') {
		$articleUrl = self::getUrl($targetArticle, $clang, $parameters, $divider);

		if ($articleUrl[0] == '/') {
			return self::getBaseUrl(false).$articleUrl;
		}

		$baseURL = self::getBaseUrl(true);
		return $baseURL.'/'.$articleUrl;
	}

	public static function getUrl($targetArticle, $clang = null, $parameters = array(), $divider = '&amp;') {
		$articleID = self::resolveArticle($targetArticle);
		$article   = sly_Util_Article::findById($articleID, $clang);

		return $article->getUrl($parameters, $divider);
	}

	public static function getBaseUrl($addScriptPath = false) {
		$baseURL = 'http'.(!empty($_SERVER['HTTPS']) ? 's' : '').'://'.self::getHost().($addScriptPath ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '');
		return rtrim($baseURL, '/');
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
	 * @return string  the host or an empty string if none found
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

	public static function getHost() {
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))   return $_SERVER['HTTP_X_FORWARDED_HOST'];
		if (isset($_SERVER['HTTP_HOST']))               return $_SERVER['HTTP_HOST'];
		if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) return $_SERVER['HTTP_X_FORWARDED_SERVER'];
		if (isset($_SERVER['SERVER_NAME']))             return $_SERVER['SERVER_NAME'];

		return '';
	}

	/**
	 * @return boolean
	 */
	public function isSecure() {
		return
			(isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
	}
}
