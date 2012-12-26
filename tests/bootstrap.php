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

$travis = getenv('TRAVIS') !== false;
$here   = dirname(__FILE__);
$root   = dirname($here);

define('SLY_IS_TESTING',        true);
define('SLY_TESTING_USER_ID',   1);
define('SLY_TESTING_USE_CACHE', $travis ? false : true);

// define vital paths
define('SLY_BASE',          $root);
define('SLY_DEVELOPFOLDER', $here.DIRECTORY_SEPARATOR.'develop');
define('SLY_MEDIAFOLDER',   $here.DIRECTORY_SEPARATOR.'mediapool');
define('SLY_ADDONFOLDER',   $here.DIRECTORY_SEPARATOR.'addons');
define('SLY_VENDORFOLDER',  $root.DIRECTORY_SEPARATOR.'vendor');
define('SLY_DATAFOLDER',    $here.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'run-'.uniqid());

if (!is_dir(SLY_MEDIAFOLDER)) mkdir(SLY_MEDIAFOLDER);
if (!is_dir(SLY_ADDONFOLDER)) mkdir(SLY_ADDONFOLDER);
if (!is_dir(SLY_DATAFOLDER))  mkdir(SLY_DATAFOLDER, 0777, true);

// set our own config folder
if ($travis) {
	define('SLY_CONFIGFOLDER', $here.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'travis');
}
else {
	define('SLY_CONFIGFOLDER', $here.DIRECTORY_SEPARATOR.'config');
}

// load core system
$slyAppName = 'tests';
$slyAppBase = 'tests';
require $here.'/../master.php';

// do not overwrite config or write the cachefile
sly_Core::config()->setFlushOnDestruct(false);

// add the dummy lib
sly_Loader::addLoadPath($here.DIRECTORY_SEPARATOR.'lib');

// make tests autoloadable
sly_Loader::addLoadPath($here.'/tests', 'sly_');

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

// add a dummy i18n
$i18n = new sly_I18N('de', $here.DIRECTORY_SEPARATOR.'addons');
sly_Core::setI18N($i18n);

// clear current cache
sly_Core::cache()->flush('sly');
