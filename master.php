<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('SLY_START_TIME', microtime(true));

if (!defined('SLY_IS_TESTING')) {
	define('SLY_IS_TESTING', false);
}

// remove magic quotes (function is deprecated as of PHP 5.4, so we either
// have to check the PHP version or suppress the E_DEPRECATED warning)
if (@get_magic_quotes_gpc()) {
	function stripslashes_ref(&$value) {
		$value = stripslashes($value);
	}

	array_walk_recursive($_GET,     'stripslashes_ref');
	array_walk_recursive($_POST,    'stripslashes_ref');
	array_walk_recursive($_COOKIE,  'stripslashes_ref');
	array_walk_recursive($_REQUEST, 'stripslashes_ref');
}

// remove all globals
if (ini_get('register_globals')) {
	$superglobals = array('_GET', '_POST', '_REQUEST', '_ENV', '_FILES', '_SESSION', '_COOKIE', '_SERVER');
	$keys         = array_keys($GLOBALS);

	foreach ($keys as $key) {
		if (!in_array($key, $superglobals) && $key !== 'GLOBALS' && $key !== 'slyAppName' && $key !== 'slyAppBase') {
			unset($$key);
		}
	}

	unset($superglobals, $key, $keys);
}

// we're using UTF-8 everywhere
mb_internal_encoding('UTF-8');

// define that the path to the core is here
define('SLY_COREFOLDER', realpath(dirname(__FILE__)));

// define constants for system wide important paths if they are not set already
if (!defined('SLY_BASE'))          define('SLY_BASE',          realpath(SLY_COREFOLDER.'/../../'));
if (!defined('SLY_SALLYFOLDER'))   define('SLY_SALLYFOLDER',   SLY_BASE.DIRECTORY_SEPARATOR.'sally');
if (!defined('SLY_DEVELOPFOLDER')) define('SLY_DEVELOPFOLDER', SLY_BASE.DIRECTORY_SEPARATOR.'develop');
if (!defined('SLY_VENDORFOLDER'))  define('SLY_VENDORFOLDER',  SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'vendor');
if (!defined('SLY_DATAFOLDER'))    define('SLY_DATAFOLDER',    SLY_BASE.DIRECTORY_SEPARATOR.'data');
if (!defined('SLY_DYNFOLDER'))     define('SLY_DYNFOLDER',     SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'dyn');
if (!defined('SLY_MEDIAFOLDER'))   define('SLY_MEDIAFOLDER',   SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'mediapool');
if (!defined('SLY_CONFIGFOLDER'))  define('SLY_CONFIGFOLDER',  SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'config');
if (!defined('SLY_ADDONFOLDER'))   define('SLY_ADDONFOLDER',   SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'addons');

// define these PHP 5.3 constants here so that they can be used in YAML files
// (if someone really decides to put PHP code in their config files).
if (!defined('E_DEPRECATED'))      define('E_DEPRECATED',      8192);  // PHP 5.3
if (!defined('E_USER_DEPRECATED')) define('E_USER_DEPRECATED', 16384); // PHP 5.3

// init loader
require_once SLY_COREFOLDER.'/loader.php';

// init container
$container = new sly_Container();
$container->setConfigDir(SLY_CONFIGFOLDER);
$container->setApplicationInfo($slyAppName, $slyAppBase);
sly_Core::setContainer($container);

// load core config (be extra careful because this is the first attempt to write
// to the filesystem on new installations)
try {
	$config = $container->getConfig();
	$config->loadStatic(SLY_COREFOLDER.'/config/sallyStatic.yml');
	$config->loadLocalConfig();
	$config->loadProjectConfig();
	$config->loadDevelopConfig();
}
catch (sly_Util_DirectoryException $e) {
	$dir = sly_html($e->getDirectory());

	header('Content-Type: text/html; charset=UTF-8');
	die(
		'Could not create data directory in <strong>'.$dir.'</strong>.<br />'.
		'Please check your filesystem permissions and ensure that PHP is allowed<br />'.
		'to write in <strong>'.SLY_DATAFOLDER.'</strong>. In most cases this can<br />'.
		'be fixed by creating the directory via FTP and chmodding it to <strong>0777</strong>.'
	);
}
catch (Exception $e) {
	header('Content-Type: text/plain; charset=UTF-8');
	die('Could not load core configuration: '.$e->getMessage());
}

// init basic error handling
$errorHandler = $container->getErrorHandler();
$errorHandler->init();

// Sync?
if (!sly_Core::isSetup()) {
	// Cache-Util initialisieren
	sly_Util_Cache::registerListener();
}

// Check for system updates
$coreVersion  = sly_Core::getVersion('X.Y.Z');
$knownVersion = sly_Util_Versions::get('sally');

if ($knownVersion !== $coreVersion) {
	// TODO: implement some clever update mechanism
	sly_Util_Versions::set('sally', $coreVersion);
}

// cleanup
unset($container, $config, $errorHandler, $coreVersion, $knownVersion);
