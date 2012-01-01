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
 * @defgroup redaxo        REDAXO Legacy-API
 * @defgroup redaxo2       REDAXO OO-API
 * @defgroup authorisation Authorisation
 * @defgroup cache         Caches
 * @defgroup controller    Controller
 * @defgroup core          Systemkern
 * @defgroup database      Datenbank
 * @defgroup event         Eventsystem
 * @defgroup form          Formular-Framework
 * @defgroup i18n          I18N
 * @defgroup layout        Layouts
 * @defgroup model         Models
 * @defgroup registry      Registry
 * @defgroup service       Services
 * @defgroup table         Tabellen
 * @defgroup util          Utilities
 */

function sly_get($name, $type, $default = '') {
	$value = _rex_array_key_cast($_GET, $name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_post($name, $type, $default = '') {
	$value = _rex_array_key_cast($_POST, $name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_request($name, $type, $default = '') {
	$value = _rex_array_key_cast($_REQUEST, $name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_getArray($name, $types, $default = array()) {
	$values = sly_makeArray(isset($_GET[$name]) ? $_GET[$name] : $default);

	foreach ($values as &$value) {
		if (is_array($value)) {
			unset($value);
			continue;
		}

		$value = _rex_cast_var($value, $types, $default, 'found', false); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($types) == 'string' ? trim($value) : $value;
	}

	return $values;
}

function sly_postArray($name, $types, $default = array()) {
	$values = sly_makeArray(isset($_POST[$name]) ? $_POST[$name] : $default);

	foreach ($values as $idx => &$value) {
		if (is_array($value)) {
			unset($values[$idx]);
			continue;
		}

		$value = _rex_cast_var($value, $types, $default, 'found', false); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($types) == 'string' ? trim($value) : $value;
	}

	return $values;
}

function sly_requestArray($name, $types, $default = array()) {
	return isset($_POST[$name]) ?
		sly_postArray($name, $types, $default) : sly_getArray($name, $types, $default);
}

function sly_html($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Schlüsselbasiertes Mergen
 *
 * Gibt es hierfür eine PHP-interne Alternative?
 *
 * @param  array $array1  das erste Array
 * @param  array $array2  das zweite Array
 * @return array          das Array mit den Werten aus beiden Arrays
 */
function sly_merge($array1, $array2) {
	$result = $array1;
	foreach ($array2 as $key => $value) {
		if (!in_array($key, array_keys($result),true)) $result[$key] = $value;
	}
	return $result;
}

/**
 * Hilfsfunktion: Ersetzen von Werten in Array
 *
 * Sucht in einem Array nach Elementen und ersetzt jedes
 * Vorkommen durch einen neuen Wert.
 *
 * @param  array $array        das Such-Array
 * @param  mixed $needle       der zu suchende Wert
 * @param  mixed $replacement  der Ersetzungswert
 * @return array               das resultierende Array
 */
function sly_arrayReplace($array, $needle, $replacement) {
	$i = array_search($needle, $array);
	if ($i === false) return $array;
	$array[$i] = $replacement;
	return sly_arrayReplace($array, $needle, $replacement);
}

/**
 * Hilfsfunktion: Löschen von Werten aus einem Array
 *
 * Sucht in einem Array nach Elementen und löscht jedes
 * Vorkommen.
 *
 * @param  array $array   das Such-Array
 * @param  mixed $needle  der zu suchende Wert
 * @return array          das resultierende Array
 */
function sly_arrayDelete($array, $needle) {
	$i = array_search($needle, $array);
	if ($i === false) return $array;
	unset($array[$i]);
	return sly_arrayDelete($array, $needle);
}

/**
 * Macht aus einem Skalar ein Array
 *
 * @param  mixed $element  das Element
 * @return array           leeres Array für $element = null, einelementiges
 *                         Array für $element = Skalar, sonst direkt $element
 */
function sly_makeArray($element) {
	if ($element === null)  return array();
	if (is_array($element)) return $element;
	return array($element);
}

/**
 * translate a key
 *
 * You can give more arguments than just the key to have them inserted at the
 * special placeholders ({0}, {1}, ...).
 *
 * @param  string $key  the key to find in the current language database
 * @return string       the found translation or a string like '[translate:X]'
 */
function t($key) {
	$args = func_get_args();
	$i18n = sly_Core::getI18N();

	if (!($i18n instanceof sly_I18N)) {
		throw new sly_Exception('No translation database set in sly_Core!');
	}

	$func = array($i18n, 'msg');
	return call_user_func_array($func, $args);
}

/**
 * translate a key and return result XHTML-encoded
 *
 * You can give more arguments than just the key to have them inserted at the
 * special placeholders ({0}, {1}, ...).
 *
 * @param  string $key  the key to find in the current language database
 * @return string       the found translation or a string like '[translate:X]' (always XHTML-safe)
 */
function ht($index) {
	return sly_html(t($index));
}

/**
 * Übersetzt den Text $text, falls dieser mit dem Präfix "translate:" beginnt.
 *
 * @param  string $text  der zu übersetzende Text
 * @param  bool   $html  wenn true, wird das Ergebnis durch sly_html() behandelt
 * @return string        der übersetzte Wert
 */
function sly_translate($text, $html = false) {
	$transKey = 'translate:';

	if (sly_Util_String::startsWith($text, $transKey)) {
		$text = t(mb_substr($text, 10));
	}

	return $html ? sly_html($text) : $text;
}

function sly_ini_get($key, $default = null) {
	$res = ini_get($key);
	if (empty($res)) return $default;
	$res = trim($res);

	// interpret numeric values
	if (preg_match('#(^[0-9]+)([ptgmk])$#i', $res, $matches)) {
		$last = strtolower($matches[2]);
		$res  = strtolower($matches[1]);

		switch ($last) {
			case 'p': $res *= 1024;
			case 't': $res *= 1024;
			case 'g': $res *= 1024;
			case 'm': $res *= 1024;
			case 'k': $res *= 1024;
		}
	}

	// interpret boolean values
	switch (strtolower($res)) {
		case 'on':
		case 'yes':
		case 'true':
			$res = true;
			break;

		case 'off':
		case 'no':
		case 'false':
			$res = false;
			break;
	}

	return $res;
}

function sly_dump() {
	print '<pre>';
	$args = func_get_args();
	call_user_func_array('var_dump', $args);
	print '</pre>';
}
