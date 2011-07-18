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
class sly_Util_String {
	/**
	 * Prüfen, ob Wert numerisch ist
	 *
	 * @param  mixed $value  der zu prüfende Wert
	 * @return bool          true, wenn der Wert verlustfrei in Zahl umgeformt werden kann, sonst false
	 */
	public static function isInteger($value) {
		if (is_int($value)) return true;
		if (is_string($value) && strval(intval($value)) === $value) return true;
		return false;
	}

	public static function startsWith($haystack, $needle) {
		$haystack = (string) $haystack;
		$needle   = (string) $needle;

		if (strlen($needle) > strlen($haystack)) return false;
		if ($haystack == $needle || strlen($needle) == 0) return true;
		return strstr($haystack, $needle) == $haystack;
	}

	public static function endsWith($haystack, $needle) {
		$haystack = (string) $haystack;
		$needle   = (string) $needle;

		if (strlen($needle) > strlen($haystack)) return false;
		if ($haystack == $needle || strlen($needle) == 0) return true;
		return substr($haystack, -strlen($needle)) == $needle;
	}

	public static function strToUpper($string) {
		if (is_string($string)) {
			$string = str_replace('ß', 'ss', $string);
			$string = mb_strtoupper($string, 'UTF-8');
		}

		return $string;
	}

	public static function replaceUmlauts($text) {
		static $specials = array(
			array('Ä', 'ä',  'á', 'à', 'é', 'è', 'Ö',  'ö',  'Ü' , 'ü' , 'ß', '&', 'ç'),
			array('Ae','ae', 'a', 'a', 'e', 'e', 'Oe', 'oe', 'Ue', 'ue', 'ss', '', 'c')
		);

		return str_replace($specials[0], $specials[1], $text);
	}

	public static function formatNumber($number, $decimals = -1) {
		$locale   = localeconv();
		$decimals = $decimals < 0 ? $locale['frac_digits'] : $decimals;
		return number_format($number, $decimals, $locale['decimal_point'], $locale['thousands_sep']);
	}

	public static function formatStrftime($format, $timestamp = null) {
		if ($timestamp === null) $timestamp = time();
		elseif (!self::isInteger($timestamp)) $timestamp = strtotime($timestamp);

		$str = strftime($format, $timestamp);

		// Windows systems do not support UTF-8 locales, so we try to fix this
		// by manually converting the string. THis should only happen on dev
		// machines, so don't worry about performance.

		if (PHP_OS === 'WINNT' && function_exists('iconv')) {
			$str = iconv('ISO-8859-1', 'UTF-8', $str);
		}

		return $str;
	}

	/**
	 * Die folgende Funktion schneidet einen Text nach der einer bestimmten Anzahl
	 * von Zeichen ab und hängt ... an, falls etwas abgeschnitten wurde.
	 *
	 * @param  $text
	 * @param  $maxLength
	 * @return string
	 */
	public static function cutText($text, $maxLength, $suffix = '...') {
		$text = preg_replace('/<br\s*\/>/', '##BR##', $text);
		$text = preg_replace('/<\/h[1-6]>/', '##BR####BR##', $text);
		$text = str_replace('</p>', '##BR####BR##', $text);

		$text = strip_tags($text);
		$text = str_replace('##BR##', '<br />', $text);

		$return = substr($text, 0, $maxLength);

		if (strlen($text) > $maxLength) {
			$return .= $suffix;
		}

		return $return;
	}
	/**
	 * shortens a filename to a max lenght and leaves an optional suffix
	 * prior to the extension
	 *
	 * @param string $name          filename to be shorten
	 * @param int    $maxLength     maximum string length
	 * @param int    $suffixLength  length of last characters
	 * @return string  returns false on error
	 */
	public static function shortenFilename($name, $maxLength, $suffixLength = 3) {
		if (!is_string($name) || !$name || !is_int($maxLength) || $maxLength < 1
			|| !is_int($suffixLength) || $suffixLength < 0 ) {

			return false;
		}
		$pos = strrpos($name, '.');
		if ($pos === false || $pos <= $maxLength) return $name;

		$shortname  = substr($name, 0, min($maxLength - $suffixLength, $pos));
		if ($maxLength - $suffixLength < $pos) {
			if ($suffixLength > 0) $shortname .= '…';
			$shortname .= substr($name, $pos - $suffixLength, 3);
		}
		$shortname .= substr($name, $pos);

		return $shortname;
	}

	/**
	 * Dateigröße formatieren
	 *
	 * Diese Methode übernimmt eine Dateigröße in Byte und rechnet sie solange
	 * in größere Einheiten um, bis eine sinnvolle Entsprechung gefunden wurde.
	 * Werte, die kleiner als 1024 Byte sind, werden als "< 1 KB" zurückgegeben.
	 * Aus diesem Grund sollte die Ausgabe dieser Methode natürlich wie jede
	 * andere auch vor dem Einbetten in HTML durch htmlspecialchars() behandelt
	 * werden.
	 *
	 * Die letzte Einheit ist ein Yottabyte.
	 *
	 * @param  int $size  die Dateigröße in Byte
	 * @return string     die Dateigröße im Format "X.YY _B" oder "< 1 KB"
	 */
	public static function formatFilesize($size, $precision=2, $unit='Bytes') {
		// Wir teilen in die Funktion immer durch 999 anstatt durch 1024, damit
		// als Größenangaben nicht "1023 KB", sondern "0,99 MB" errechnet werden.
		// Das ist benutzerfreundlicher.
		if ($size < 999) {
			return $size.' '.$unit;
		}
		$unitPrefixes = array('K','M','G','T','P','E','Z','Y');
		while ($size > 999 && !empty($unitPrefixes)) {
			$size /= 1024.0;
			$unitPrefix = array_shift($unitPrefixes);
		}
		return self::formatNumber($size, $precision).' '.$unitPrefix.$unit;
	}
	/**
	 * Führt eine Liste zusammen
	 *
	 * Diese Methode fügt eine Liste zu einem String zusammen. Im Unterschied
	 * zum normalen implode() schreibt sie jedoch zwischen die letzten beiden
	 * Elemente kein Komma, sondern per default ein " und ", um eine
	 * menschenlesbarere Ausgabe zu erhalten.
	 *
	 * @param  array  $list  die Liste von Elementen
	 * @param  string $last  das Wort, das zwischen die letzten beiden Elemente gesetzt werden soll
	 * @return string        die Liste als String (zum Beispiel "a, b, c und d")
	 */
	public static function humanImplode($list, $last = ' und ') {
		switch (count($list)) {
			case 0: return '';
			case 1: return $list[0];
			case 2: return $list[0].$last.$list[1];
			default: return implode(', ', array_slice($list, 0, -1)).$last.$list[count($list)-1];
		}
	}

	public static function getRandomString($maxLen = 5, $minLen = 1, $charset = null) {
		if ($minLen > $maxLen) {
			list($minLen, $maxLen) = array($maxLen, $minLen);
		}

		$count = mt_rand($minLen, $maxLen);
		$chars = $charset === null ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789' : $charset;
		$last  = strlen($chars)-1;
		$s     = '';

		for (; $count > 0; --$count) {
			$s .= $chars[mt_rand(0, $last)];
		}

		return str_shuffle($s);
	}

	public static function secondsToAbsTime($seconds) {
		$time    = '';
		$days    = 0;
		$hours   = 0;
		$minutes = 0;
		$seconds = (float) abs(intval($seconds));

		$days    = floor($seconds / (24*3600)); $seconds -= $days * (24*3600);
		$hours   = floor($seconds / 3600);      $seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);        $seconds -= $minutes * 60;

		if ($days > 0) $time .= $days.'d ';
		$time .= sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

		return $time;
	}

	public static function preg_startsWith($pattern, $subject) {
		preg_match($pattern, $subject, $treffer);
		return !empty($treffer);
	}
	
	public static function escapePHP($text) {
		return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $text);
	}
}
