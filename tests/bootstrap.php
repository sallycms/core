<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
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
$cfg    = getenv('CFG');
$here   = __DIR__;
$root   = dirname($here);

if(empty($cfg)) {
	$cfg = 'mysql';
}

if(empty($cfg) && $travis) {
	$cfg = 'mysql-travis';
}


// define Testuser
if (!defined('SLY_TESTING_USER_ID')) define('SLY_TESTING_USER_ID', 1);

// define vital paths
if (!defined('SLY_BASE'))          define('SLY_BASE',          $root);
if (!defined('SLY_DEVELOPFOLDER')) define('SLY_DEVELOPFOLDER', $here.DIRECTORY_SEPARATOR.'develop');
if (!defined('SLY_ADDONFOLDER'))   define('SLY_ADDONFOLDER',   $here.DIRECTORY_SEPARATOR.'addons');
if (!defined('SLY_VENDORFOLDER'))  define('SLY_VENDORFOLDER',  $root.DIRECTORY_SEPARATOR.'vendor');
if (!defined('SLY_DATAFOLDER'))    define('SLY_DATAFOLDER',    $here.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'run-'.uniqid());

if (!is_dir(SLY_ADDONFOLDER)) mkdir(SLY_ADDONFOLDER);
if (!is_dir(SLY_DATAFOLDER))  mkdir(SLY_DATAFOLDER, 0777, true);

// set our own config folder
define('SLY_CONFIGFOLDER', $here.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$cfg);

// autoloader
$loader = require $here.'/../autoload.php';
$loader->add('sly_', $here.DIRECTORY_SEPARATOR.'lib');
$loader->add('sly_', $here.DIRECTORY_SEPARATOR.'tests');

$container = new sly_Container();

$container['sly-config-reader'] = $container->share(function() {
	return new sly_Test_Configuration_Reader();
});

$container['sly-config-writer'] = $container->share(function() {
	return new sly_Test_Configuration_Writer();
});

// load core system
sly_Core::boot($loader, 'test', 'tests', 'tests', $container);

/* @var $conn sly\Database\Connection */
$dbDriver  = $container['sly-config']->get('database/driver');
$conn      = $container['sly-dbal-connection'];
$dbManager = $conn->getSchemaManager();

foreach($dbManager->listTables() as $table) {
	$dbManager->dropTable($table);
}

$conn->exec(file_get_contents(SLY_BASE.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$dbDriver.'.sql'));

// init the app
$app = new sly_App_Tests($container, 1);
$container->set('sly-app', $app);
$app->initialize();
