<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Getter Funktionen zum Handling von Superglobalen Variablen
 *
 * @package redaxo4
 */


/**
 * Durchsucht das Array $haystack nach dem Schlüssel $needle.
 *
 * Falls ein Wert gefunden wurde wird dieser nach
 * $vartype gecastet und anschließend zurückgegeben.
 *
 * Falls die Suche erfolglos endet, wird $default zurückgegeben
 *
 * @access private
 */
function _rex_array_key_cast($haystack, $needle, $vartype, $default = '', $addslashes = true)
{
	if (!is_array($haystack)) {
		trigger_error('Array expected for $haystack in _rex_array_key_cast()!', E_USER_ERROR);
		exit();
	}

	if (!is_scalar($needle)) {
		trigger_error('Scalar expected for $needle in _rex_array_key_cast()!', E_USER_ERROR);
		exit();
	}

	if (array_key_exists($needle, $haystack)) {
		return _rex_cast_var($haystack[$needle], $vartype, $default, 'found', $addslashes);
	}

	if ($default === '') {
		return _rex_cast_var($default, $vartype, $default, 'default', $addslashes);
	}

	return $default;
}

/**
 * Castet die Variable $var zum Typ $vartype
 *
 * Mögliche PHP-Typen sind:
 *  - bool (auch boolean)
 *  - int (auch integer)
 *  - double
 *  - string
 *  - float
 *  - real
 *  - object
 *  - array
 *  - '' (nicht casten)
 *
 * Mögliche REDAXO-Typen sind:
 *  - rex-article-id
 *  - rex-category-id
 *  - rex-clang-id
 *  - rex-template-id
 *  - rex-slot
 *  - rex-slice-id
 *  - rex-module-id
 *  - rex-action-id
 *  - rex-media-id
 *  - rex-mediacategory-id
 *  - rex-user-id
 *
 * @access private
 */
function _rex_cast_var($var, $vartype, $default, $mode, $addslashes = true)
{
	if (!is_string($vartype)) {
		trigger_error('String expected for $vartype in _rex_cast_var()!', E_USER_ERROR);
	}

	switch ($vartype) {
		// ---------------- REDAXO types
		case 'rex-article-id':
			$var = (int) $var;
			if ($mode == 'found') {
				if (!sly_Util_Article::exists($var)) {
					$var = (int) $default;
				}
			}
			break;

		case 'rex-category-id':
			$var = (int) $var;
			if ($mode == 'found') {
				if (!sly_Util_Category::isValid(sly_Util_Category::findById($var))) {
					$var = (int) $default;
				}
			}
			break;

		case 'rex-clang-id':
			$var = (int) $var;
			if ($mode == 'found' && !sly_Util_Language::exists($var)) {
				$var = (int) $default;
			}
			break;

		case 'rex-template-id':
		case 'rex-slice-id':
		case 'rex-module-id':
		case 'rex-action-id':
		case 'rex-media-id':
		case 'rex-mediacategory-id':
		case 'rex-user-id':
			// erstmal keine weitere validierung
			$var = (int) $var;
			break;

		// ---------------- PHP types
		case 'bool':
		case 'boolean':
			$var = (boolean) $var;
			break;

		case 'int':
		case 'integer':
			$var = (int) $var;
			break;

		case 'uint':
		case 'uinteger':
			$var = abs((int) $var);
			break;

		case 'double':
			$var = (double) $var;
			break;

		case 'udouble':
			$var = abs((double) $var);
			break;

		case 'float':
		case 'real':
			$var = (float) $var;
			break;

		case 'ufloat':
		case 'ureal':
			$var = abs((float) $var);
			break;

		case 'rex-slot':
		case 'rex-ctype-id':
		case 'string':
			// Alte REDAXO-AddOns verlassen sich auf die Magic Quotes, die aus
			// dieser Funktion rauskommen sollten. Neue AddOns verwenden sly_*.
			$var = trim((string) $var);
			if ($addslashes) $var = addslashes($var);
			break;

		case 'object':
			$var = (object) $var;
			break;

		case 'array':
			$var = empty($var) ? array() : (array) $var;
			break;

		// kein Cast, nichts tun
		case '':
			break;

		// Typo?
		default:
			trigger_error('Unexpected vartype "'.$vartype.'" in _rex_cast_var()!', E_USER_ERROR);
	}

	return $var;
}
