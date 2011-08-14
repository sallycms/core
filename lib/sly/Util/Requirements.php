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
class sly_Util_Requirements {
	const OK      = 2; ///< int
	const WARNING = 1; ///< int
	const FAILED  = 0; ///< int

	/**
	 * @return array
	 */
	public function phpVersion() {
		$version = $this->numPHPVersion();
		$current = $this->versionValue($version);
		$best    = $this->versionValue('5.3');
		$ok      = $this->versionValue('5.1');

		return $this->result($version, $current >= $best ? self::OK : ($current >= $ok ? self::WARNING : self::FAILED));
	}

	/**
	 * @return array
	 */
	public function mySQLVersion() {
		if (function_exists('mysqli_get_client_version')) {
			$versionNum = mysqli_get_client_version();
			$version    = sprintf('%d.%d.%d', floor($versionNum / 10000), floor($versionNum / 100) % 100, $versionNum % 10000);
			return $this->result($version.' (MySQLi)', $versionNum >= 50000 ? self::OK : self::FAILED);
		}
		elseif (function_exists('mysql_get_client_info')) {
			$version = mysql_get_client_info();
			return $this->result($version.' (MySQL)', version_compare($version, '5.0', '>=') ? self::OK : self::FAILED);
		}
		else {
			return $this->failed('translate:no_mysql_mysqli');
		}
	}

	/**
	 * @return array
	 */
	public function gd() {
		if (!function_exists('gd_info')) {
			return $this->failed('translate:unavailable');
		}

		$version = gd_info();
		return $this->ok($version['GD Version']);
	}

	/**
	 * @return array
	 */
	public function xmlReader() {
		return class_exists('XMLReader') ? $this->ok('translate:available') : $this->warning('translate:unavailable');
	}

	/**
	 * @return array
	 */
	public function xmlWriter() {
		return class_exists('XMLWriter') ? $this->ok('translate:available') : $this->warning('translate:unavailable');
	}

	/**
	 * @return array
	 */
	public function curl() {
		if (!function_exists('curl_init')) {
			return $this->warning('translate:unavailable');
		}

		$ch = curl_init();

		//Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//Set the URL
		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		//Execute the fetch
		$data = curl_exec($ch);
		//Close the connection
		curl_close($ch);

		return empty($data) ? $this->warning('translate:timeout') : $this->ok('translate:available');
	}

	/**
	 * @return array
	 */
	public function allowURLfopen() {
		if (ini_get('allow_url_fopen') == 0) {
			return $this->warning('translate:forbidden');
		}

		ini_set('default_socket_timeout', 3);
		$data = @file_get_contents('http://www.google.com/');

		return empty($data) ? $this->warning('translate:timeout') : $this->ok('translate:allowed');
	}

	/**
	 * @return array
	 */
	public function execTime() {
		$maxTime = ini_get('max_execution_time');

		if ($maxTime >= 20) {
			return $this->ok($maxTime.'s');
		}
		else {
			if (ini_set('max_execution_time', 20) === false) {
				return $this->failed($maxTime.'s');
			}
			else {
				return $this->warning(t('exec_time', $maxTime));
			}
		}
	}

	/**
	 * @return array
	 */
	public function memoryLimit() {
		$mem = ini_get('memory_limit');

		if ($mem >= 64) return $this->ok($mem.'B');
		else if (ini_set('memory_limit', '64M') !== false) return $this->warning($mem);
		else if ($mem >= 16) return $this->ok($mem.'B');
		else if (empty($mem)) return $this->warning('translate:unknown');
		else return $this->failed($mem.'B');
	}

	/**
	 * @return array
	 */
	public function nonsenseSecurity() {
		$safe_mode    = ini_get('safe_mode');
		$open_basedir = ini_get('open_basedir');

		if (!$safe_mode && !$open_basedir) return $this->ok('translate:none');
		else if (!$safe_mode && $open_basedir) return $this->warning('open_basedir');
		else return $this->failed($open_basedir ? 'translate:safemode_openbasedir' : 'safe_mode');
	}

	/**
	 * @return array
	 */
	public function shortOpenTags() {
		return ini_get('short_open_tag') == 0 ? $this->failed('translate:deactivated') : $this->ok('translate:activated');
	}

	/**
	 * @return array
	 */
	public function registerGlobals() {
		return ini_get('register_globals') == 0 ? $this->ok('translate:deactivated') : $this->warning('translate:activated');
	}

	/**
	 * @return array
	 */
	public function magicQuotes() {
		return ini_get('magic_quotes_gpc') == 0 ? $this->ok('translate:deactivated') : $this->warning('translate:activated');
	}

	/**
	 * @param  string $ext
	 * @return string
	 */
	private function numPHPVersion($ext = '') {
		$result = empty($ext) ? phpversion() : phpversion($ext);
		$pos    = strpos($result, '-');
		return $pos === false ? $result : substr($result, 0, $pos);
	}

	/**
	 * @param  string $version
	 * @param  double $versishifton
	 * @return int
	 */
	private function versionValue($version, $shift = 0.01) {
		$result = 0;
		$factor = 1.0;

		do {
			$pos = strpos($version, '.');
			if ($pos === false) $pos = strlen($version);

			$cur     = substr($version, 0, $pos);
			$version = substr($version, $pos+1);

			$result += $factor * $cur;
			$factor *= $shift;
		}
		while ($version != '');

		return $result;
	}

	/**
	 * @param  mixed $result
	 * @return string
	 */
	public function getClassName($result) {
		static $classes = array(self::WARNING => 'warning', self::OK => 'ok', self::FAILED => 'failed');
		$status = is_array($result) ? $result['status'] : (int) $result;
		return isset($classes[$status]) ? $classes[$status] : 'unknown';
	}

	/**
	 * @param  string $text
	 * @param  int    $status
	 * @return array
	 */
	private function result($text, $status) {
		return array('text' => rex_translate($text), 'status' => $status);
	}

	/**
	 * @param  string $text
	 * @return array
	 */
	private function ok($text) {
		return $this->result($text, self::OK);
	}

	/**
	 * @param  string $text
	 * @return array
	 */
	private function failed($text) {
		return $this->result($text, self::FAILED);
	}

	/**
	 * @param  string $text
	 * @return array
	 */
	private function warning($text) {
		return $this->result($text, self::WARNING);
	}
}
