<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Sonstige Funktionen
 *
 * @package redaxo4
 */

/**
 * Übersetzt den text $text, falls dieser mit dem prefix "translate:" beginnt.
 *
 * @param  string    $text     der zu übersetzende Text
 * @param  sly_I18N  $i18n
 * @param  bool      $as_html  wenn true, wird das Ergebnis durch sly_html() behandelt
 * @return string              der übersetzte Wert
 */
function rex_translate($text, sly_I18N $i18n = null, $as_html = true) {
	$transKey = 'translate:';

	if (sly_Util_String::startsWith($text, $transKey)) {
		if (!$i18n) {
			$i18n = sly_Core::getI18N();
		}
		$text = $i18n->msg(mb_substr($text, mb_strlen($transKey)));
	}

	return $as_html ? sly_html($text) : $text;
}

/**
 * Trennt einen String an Leerzeichen auf.
 * Dabei wird beachtet, dass Strings in " zusammengehören
 */
function rex_split_string($string) {
	$spacer = '@@@REX_SPACER@@@';
	$result = array();

	// TODO: mehrfachspaces hintereinander durch einfachen ersetzen
	$string = ' '.trim($string).' ';

	// Strings mit Quotes heraussuchen
	$pattern = '!(["\'])(.*)\\1!U';
	preg_match_all($pattern, $string, $matches);
	$quoted = isset($matches[2]) ? $matches[2] : array();

	// Strings mit Quotes maskieren
	$string = preg_replace($pattern, $spacer, $string);

	// ----------- z.b. 4 "av c" 'de f' ghi
	if (strpos($string, '=') === false) {
		$parts = explode(' ', $string);
		foreach ($parts as $part) {
			if (empty($part)) continue;

			if ($part == $spacer) {
				$result[] = array_shift($quoted);
			}
			else {
				$result[] = $part;
			}
		}
	}
	// ------------ z.b. a=4 b="av c" y='de f' z=ghi
	else {
		$parts = explode(' ', $string);
		foreach ($parts as $part) {
			if (empty($part)) continue;

			$variable = explode('=', $part);

			if (empty($variable[0]) || empty($variable[1])) {
				continue;
			}

			$var_name  = $variable[0];
			$var_value = $variable[1];

			if ($var_value == $spacer) {
				$var_value = array_shift($quoted);
			}

			$result[$var_name] = $var_value;
		}
	}

	return $result;
}
