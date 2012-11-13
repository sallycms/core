<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

if (PHP_SAPI !== 'cli') {
	die('This script must be run from CLI.');
}

$travis    = getenv('TRAVIS') !== false;
$here      = dirname(__FILE__);
$sallyRoot = realpath($here.'/../');

define('SLY_IS_TESTING',        true);
define('SLY_TESTING_USER_ID',   1);
define('SLY_TESTING_USE_CACHE', $travis ? false : true);

if (!defined('SLY_DATAFOLDER'))    define('SLY_DATAFOLDER',    $sallyRoot.DIRECTORY_SEPARATOR.'data');
if (!defined('SLY_DEVELOPFOLDER')) define('SLY_DEVELOPFOLDER', $here.DIRECTORY_SEPARATOR.'develop');
if (!defined('SLY_MEDIAFOLDER'))   define('SLY_MEDIAFOLDER',   $here.DIRECTORY_SEPARATOR.'mediapool');
if (!defined('SLY_ADDONFOLDER'))   define('SLY_ADDONFOLDER',   $here.DIRECTORY_SEPARATOR.'addons');
if (!defined('SLY_VENDORFOLDER'))  define('SLY_VENDORFOLDER',  $sallyRoot.DIRECTORY_SEPARATOR.'vendor');

if (!is_dir(SLY_MEDIAFOLDER)) mkdir(SLY_MEDIAFOLDER);
if (!is_dir(SLY_ADDONFOLDER)) mkdir(SLY_ADDONFOLDER);

// set our own config folder
if (!defined('SLY_CONFIGFOLDER')) {
	if ($travis) {
		define('SLY_CONFIGFOLDER', $here.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'travis');
	}
	else {
		define('SLY_CONFIGFOLDER', $here.DIRECTORY_SEPARATOR.'config');
	}
}

// kill YAML cache
$files = glob($sallyRoot.'/data/dyn/internal/sally/yaml-cache/*');
if (is_array($files)) array_map('unlink', $files);

// load core system
$slyAppName = 'tests';
$slyAppBase = 'tests';
require $sallyRoot.'/master.php';
//do not overwrite config, write the cachefile
sly_Core::config()->setFlushOnDestruct(false);
// add the dummy lib
sly_Loader::addLoadPath($here.DIRECTORY_SEPARATOR.'lib');

// add DbUnit
if ($travis) {
	$dirs = glob(SLY_VENDORFOLDER.'/pear-pear.phpunit.de/*', GLOB_ONLYDIR);
	foreach ($dirs as $dir) sly_Loader::addLoadPath($dir);
}

// login the dummy user
$service = sly_Service_Factory::getUserService();
$user    = $service->findById(SLY_TESTING_USER_ID);
$service->setCurrentUser($user);

// init the app
$app = new sly_App_Tests();
sly_Core::setCurrentApp($app);
$app->initialize();

// make tests autoloadable
sly_Loader::addLoadPath(dirname(__FILE__).'/tests', 'sly_');

// clear current cache
sly_Core::cache()->flush('sly');