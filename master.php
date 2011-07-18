<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// Magic Quotes entfernen, wenn vorhanden

if (get_magic_quotes_gpc()) {
	function stripslashes_ref(&$value) {
		$value = stripslashes($value);
	}

	array_walk_recursive($_GET,     'stripslashes_ref');
	array_walk_recursive($_POST,    'stripslashes_ref');
	array_walk_recursive($_COOKIE,  'stripslashes_ref');
	array_walk_recursive($_REQUEST, 'stripslashes_ref');
}

// Register Globals entfernen

if (ini_get('register_globals')) {
	$superglobals = array('_GET', '_POST', '_REQUEST', '_ENV', '_FILES', '_SESSION', '_COOKIE', '_SERVER');
	$keys         = array_keys($GLOBALS);

	foreach ($keys as $key) {
		if (!in_array($key, $superglobals) && $key != 'GLOBALS') {
			unset($$key);
		}
	}

	unset($superglobals, $key, $keys);
}

// So, jetzt haben wir eine saubere Grundlage für unsere Aufgaben.

define('SLY_BASE',          realpath(dirname(__FILE__).'/../../'));
define('SLY_SALLYFOLDER',   SLY_BASE.DIRECTORY_SEPARATOR.'sally');
define('SLY_COREFOLDER',    SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'core');
define('SLY_DATAFOLDER',    SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'data');
define('SLY_DYNFOLDER',     SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'dyn');
define('SLY_MEDIAFOLDER',   SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'mediapool');
define('SLY_DEVELOPFOLDER', SLY_BASE.DIRECTORY_SEPARATOR.'develop');
define('SLY_ADDONFOLDER',   SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'addons');

// define these PHP 5.3 constants here so that they can be used in YAML files
// (if someone really decides to put PHP code in their config files).
if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);  // PHP 5.2
if (!defined('E_DEPRECATED'))        define('E_DEPRECATED',        8192);  // PHP 5.3
if (!defined('E_USER_DEPRECATED'))   define('E_USER_DEPRECATED',   16384); // PHP 5.3

// Loader initialisieren

require_once SLY_COREFOLDER.'/loader.php';

// Kernkonfiguration laden

$config = sly_Core::config();
$config->loadStatic(SLY_COREFOLDER.'/config/sallyStatic.yml');
$config->loadLocalConfig();
$config->loadProjectConfig();
$config->loadDevelop();

// init basic error handling
$errorHandler = sly_Core::isDeveloperMode() ? new sly_ErrorHandler_Development() : new sly_ErrorHandler_Production();
$errorHandler->init();

sly_Core::setErrorHandler($errorHandler);

// Sync?
if ($config->get('SETUP') === false) {
	// Standard-Variablen
	sly_Core::registerCoreVarTypes();

	// Cache-Util initialisieren
	sly_Util_Cache::registerListener();
}
else {
	$config->loadProjectDefaults(SLY_COREFOLDER.'/config/sallyProjectDefaults.yml');
	$config->loadLocalDefaults(SLY_COREFOLDER.'/config/sallyLocalDefaults.yml');
}

// Check for system updates
$coreVersion  = sly_Core::getVersion('X.Y.Z');
$knownVersion = sly_Util_Versions::get('sally');

if ($knownVersion !== $coreVersion) {
	// dummy: implement some clever update mechanism (if needed)
	sly_Util_Versions::set('sally', $coreVersion);
}
