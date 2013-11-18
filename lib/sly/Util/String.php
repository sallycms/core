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

	/**
	 * @param  string $haystack
	 * @param  string $needle
	 * @return boolean
	 */
	public static function startsWith($haystack, $needle) {
		$haystack = (string) $haystack;
		$needle   = (string) $needle;

		if (mb_strlen($needle) > mb_strlen($haystack)) return false;
		if ($haystack == $needle || mb_strlen($needle) == 0) return true;
		return mb_strstr($haystack, $needle) == $haystack;
	}

	/**
	 * @param  string $haystack
	 * @param  string $needle
	 * @return boolean
	 */
	public static function endsWith($haystack, $needle) {
		$haystack = (string) $haystack;
		$needle   = (string) $needle;

		if (mb_strlen($needle) > mb_strlen($haystack)) return false;
		if ($haystack == $needle || mb_strlen($needle) == 0) return true;
		return mb_substr($haystack, -mb_strlen($needle)) == $needle;
	}

	/**
	 * @param  string $string
	 * @return string
	 */
	public static function strToUpper($string) {
		if (is_string($string)) {
			$string = str_replace('ß', 'ss', $string);
			$string = mb_strtoupper($string);
		}

		return $string;
	}

	/**
	 * Format a number according to the current locale
	 *
	 * @param  numeric $number
	 * @param  int     $decimals
	 * @return string
	 */
	public static function formatNumber($number, $decimals = -1) {
		// use intl extension if possible
		if (class_exists('NumberFormatter')) {
			$formatter = new NumberFormatter(Locale::DEFAULT_LOCALE, NumberFormatter::DECIMAL);

			if ($decimals >= 0) {
				$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
			}

			return $formatter->format($number);
		}

		$locale = localeconv();
		return number_format($number, $decimals, $locale['decimal_point'], $locale['thousands_sep']);
	}

	/**
	 * @param  string $format
	 * @param  mixed  $timestamp  UNIX timestamp or datetime string (YYYY-MM-DD HH:MM:SS)
	 * @return string
	 */
	public static function formatStrftime($format, $timestamp = null) {
		if ($timestamp === null) $timestamp = time();
		elseif (!self::isInteger($timestamp)) $timestamp = strtotime($timestamp);

		// Windows systems do not support UTF-8 locales, so we try to fix this
		// by manually converting the string. This should only happen on dev
		// machines, so don't worry about performance.

		// in case $format contains UTF-8 symbols, we need to temporarily turn them into ISO chars
		if (PHP_OS === 'WINNT' && function_exists('iconv')) {
			$format = iconv('UTF-8', 'ISO-8859-1', $format);
		}

		$str = strftime($format, $timestamp);

		// now we can transform all ISO chars into nice UTF-8 symbols
		if (PHP_OS === 'WINNT' && function_exists('iconv')) {
			$str = iconv('ISO-8859-1', 'UTF-8//IGNORE', $str);
		}

		return $str;
	}

	/**
	 * @param  mixed $timestamp  UNIX timestamp or datetime string (YYYY-MM-DD HH:MM:SS)
	 * @return string
	 */
	public static function formatDate($timestamp = null) {
		return self::formatStrftime(t('dateformat'), $timestamp);
	}

	/**
	 * @param  mixed $timestamp  UNIX timestamp or datetime string (YYYY-MM-DD HH:MM:SS)
	 * @return string
	 */
	public static function formatTime($timestamp = null) {
		return self::formatStrftime(t('timeformat'), $timestamp);
	}

	/**
	 * @param  mixed $timestamp  UNIX timestamp or datetime string (YYYY-MM-DD HH:MM:SS)
	 * @return string
	 */
	public static function formatDatetime($timestamp = null) {
		if (class_exists('IntlDateFormatter')) {
			if ($timestamp === null) $timestamp = time();
			elseif (!self::isInteger($timestamp)) $timestamp = strtotime($timestamp);

			$formatter = new IntlDateFormatter(Locale::DEFAULT_LOCALE, IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
			$pattern   = $formatter->getPattern();

			// make sure we use the abbreviated month name
			$pattern = str_replace('MMMM', 'MMM', $pattern);
			$formatter->setPattern($pattern);

			return $formatter->format($timestamp);
		}

		return self::formatStrftime(t('datetimeformat'), $timestamp);
	}

	/**
	 * Cut text to a maximum length
	 *
	 * Die folgende Funktion schneidet einen Text nach der einer bestimmten
	 * Anzahl von Zeichen ab und hängt $suffix an, falls etwas abgeschnitten
	 * wurde.
	 *
	 * @param  string $text
	 * @param  int    $maxLength
	 * @param  string $suffix
	 * @return string
	 */
	public static function cutText($text, $maxLength, $suffix = '...') {
		$text = preg_replace('/<br\s*\/>/', '##BR##', $text);
		$text = preg_replace('/<\/h[1-6]>/', '##BR####BR##', $text);
		$text = str_replace('</p>', '##BR####BR##', $text);

		$text = strip_tags($text);
		$text = str_replace('##BR##', '<br />', $text);

		$return = mb_substr($text, 0, $maxLength);

		// cut off trailing newlines (if input was something like "<p>foo</p>")
		while (self::endsWith($return, '<br />')) {
			$return = mb_substr($return, 0, -6);
		}

		if (mb_strlen($text) > $maxLength) {
			$return .= $suffix;
		}

		return $return;
	}

	/**
	 * shortens a filename to a max length and leaves an optional suffix
	 * prior to the extension
	 *
	 * @param  string $name          filename to be shorten
	 * @param  int    $maxLength     maximum string length
	 * @param  int    $suffixLength  length of last characters
	 * @return string                returns false on error
	 */
	public static function shortenFilename($name, $maxLength, $suffixLength = 3) {
		if (mb_strlen($name) === 0 || $maxLength < 1 || $suffixLength < 0) {
			return false;
		}

		$pos = mb_strrpos($name, '.');
		if ($pos === false || $pos <= $maxLength) return $name;

		$shortname = mb_substr($name, 0, min($maxLength - $suffixLength, $pos));

		if ($maxLength - $suffixLength < $pos) {
			if ($suffixLength > 0) $shortname .= '…';
			$shortname .= mb_substr($name, $pos - $suffixLength, 3);
		}

		$shortname .= mb_substr($name, $pos);

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
	 * @param  int    $size       die Dateigröße in Byte
	 * @param  int    $precision
	 * @param  string $unit
	 * @return string             die Dateigröße im Format "X.YY _B" oder "< 1 KB"
	 */
	public static function formatFilesize($size, $precision = 2, $unit = 'Bytes') {
		// Wir teilen in die Funktion immer durch 999 anstatt durch 1024, damit
		// als Größenangaben nicht "1023 KB", sondern "0,99 MB" errechnet werden.
		// Das ist benutzerfreundlicher.

		if ($size < 999) {
			return $size.' '.$unit;
		}

		$unitPrefixes = array('K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

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
		$list = array_values($list);

		switch (count($list)) {
			case 0: return '';
			case 1: return $list[0];
			case 2: return $list[0].$last.$list[1];
			default: return implode(', ', array_slice($list, 0, -1)).$last.$list[count($list)-1];
		}
	}

	/**
	 * @param  int    $maxLen
	 * @param  int    $minLen
	 * @param  string $charset
	 * @return string
	 */
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

	/**
	 * @param  int $seconds
	 * @return string
	 */
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

	/**
	 * @param  double $seconds
	 * @return string
	 */
	public static function formatTimespan($seconds) {
		$ms        = $seconds - floor($seconds);
		$formatted = self::secondsToAbsTime($seconds - $ms);
		list($hours, $mins, $secs) = explode(':', $formatted);

		$hours = explode('d', $hours);
		$days  = (int) (count($hours) === 1 ? 0 : $hours[0]);
		$hours = (int) (count($hours) === 1 ? $hours[0] : $hours[1]);
		$mins  = (int) $mins;
		$secs  = (int) $secs;

		$list = array();
		if ($days)  $list[] = sprintf('%d %s', $days,    t('days_short'));
		if ($hours) $list[] = sprintf('%d %s', $hours,   t('hours_short'));
		if ($mins)  $list[] = sprintf('%d %s', $mins,    t('minutes_short'));
		if ($secs)  $list[] = sprintf('%d %s', $secs,    t('seconds_short'));
		if ($ms)    $list[] = sprintf('%d %s', $ms*1000, t('milliseconds_short'));

		return implode(' ', $list);
	}

	/**
	 * @deprecated  since 0.9, use sly_Util_File::getExtension() instead
	 *
	 * @param  string $filename
	 * @return string
	 */
	public static function getFileExtension($filename) {
		return sly_Util_File::getExtension($filename);
	}

	/**
	 * @param  mixed $value
	 * @param  array $options  list of options for representations
	 * @return string          a human readable representation of $value
	 */
	public static function stringify($value, array $options = array()) {
		$quote   = !empty($options['quote']);
		$compact = !empty($options['compact']);
		$depth   = isset($options['__depth']) ? $options['__depth'] : 2;

		$options['__depth'] = $depth - 1;

		switch (gettype($value)) {
			case 'integer':
				$value = $value;
				break;

			case 'string':
				$value = $quote ? '"'.$value.'"' : $value;
				break;

			case 'boolean':
				$value = $value ? 'true' : 'false';
				break;

			case 'double':
				$value = str_replace('.', ',', round($value, $compact ? 8 : 3));
				break;

			case 'array':
				if (empty($options['compact'])) {
					$value = print_r($value, true);
				}
				else {
					$assoc = sly_Util_Array::isAssoc($value);
					$nice  = array();

					foreach ($value as $key => $val) {
						switch (gettype($val)) {
							case 'array':
								if ($depth > 0) {
									$val = self::stringify($val, $options);
								}
								else {
									$val = 'array('.count($val).')';
								}

							default:
								$val = self::stringify($val, $options);
						}

						if ($assoc) {
							$nice[] = '"'.$key.'":'.$val;
						}
						else {
							$nice[] = $val;
						}
					}

					$nice  = implode($assoc ? ', ' : ',', $nice);
					$value = ($assoc ? '{' : '[').$nice.($assoc ? '}' : ']');
				}

				break;

			case 'object':
				if (empty($options['compact'])) {
					$value = print_r($value, true);
				}
				else {
					$value = 'object('.get_class($value).')';
				}

				break;

			case 'NULL':
				$value = 'null';
				break;

			case 'resource':
			default:
				ob_start();
				var_dump($value);
				$value = ob_get_clean();
				break;
		}

		return $value;
	}
}
