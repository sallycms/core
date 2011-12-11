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
function _rex_array_key_cast($haystack, $needle, $vartype, $default = '')
{
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
function _rex_cast_var($var, $vartype, $default, $mode)
{
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

		case 'rex-slice-id':
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

		case 'double':
		case 'float':
		case 'real':
			$var = (float) $var;
			break;

		case 'string':
			$var = trim((string) $var);
			break;

		case 'object':
			$var = (object) $var;
			break;

		case 'array':
			$var = sly_makeArray($var);
			break;

		// kein Cast, nichts tun
		case '':
			break;

		// Typo?
		default:
			throw new sly_Exception('Unexpected vartype "'.$vartype.'" in _rex_cast_var()!');
	}

	return $var;
}
