<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// remove magic quotes (function is deprecated as of PHP 5.4, so we either
// have to check the PHP version or suppress the E_DEPRECATED warning)
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	$stripper = function(&$value) {
		$value = stripslashes($value);
	};

	array_walk_recursive($_GET,     $stripper);
	array_walk_recursive($_POST,    $stripper);
	array_walk_recursive($_COOKIE,  $stripper);
	array_walk_recursive($_REQUEST, $stripper);
}

// remove all globals
if (ini_get('register_globals')) {
	$superglobals = array('_GET', '_POST', '_REQUEST', '_ENV', '_FILES', '_SESSION', '_COOKIE', '_SERVER');
	$keys         = array_keys($GLOBALS);

	foreach ($keys as $key) {
		if (!in_array($key, $superglobals) && $key !== 'GLOBALS') {
			unset($$key);
		}
	}

	unset($superglobals, $key, $keys);
}
