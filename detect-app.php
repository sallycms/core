<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// determine application based on the request URI

$base   = dirname($_SERVER['SCRIPT_NAME']);
$reqUri = substr(isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'], strlen($base));
$reqUri = preg_replace('/[?&].*$/', '', $reqUri);
$parts  = explode('/', trim($reqUri, '/'));

switch (reset($parts)) {
	case 'backend':   return array('backend',  'backend');
	case 'setup':     return array('setup',    'setup');
	case 'assets':    // fallthrough
	case 'mediapool': return array('assets',   '/');
	default:          return array('frontend', '/');
}
