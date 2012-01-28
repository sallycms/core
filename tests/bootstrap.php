<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$here      = dirname(__FILE__);
$sallyRoot = realpath($here.'/../../');

define('SLY_IS_TESTING',        true);
define('IS_SALLY_BACKEND',      true);
define('SLY_TESTING_USER_ID',   1);
define('SLY_TESTING_ROOT',      $sallyRoot);
define('SLY_TESTING_USE_CACHE', true);

if (!defined('SLY_SALLYFOLDER'))   define('SLY_SALLYFOLDER',   $sallyRoot.'/sally');
if (!defined('SLY_DEVELOPFOLDER')) define('SLY_DEVELOPFOLDER', $here.'/develop');
if (!defined('SLY_MEDIAFOLDER'))   define('SLY_MEDIAFOLDER',   $here.'/mediapool');
if (!defined('SLY_ADDONFOLDER'))   define('SLY_ADDONFOLDER',   $here.'/addons');

if (!is_dir(SLY_MEDIAFOLDER)) mkdir(SLY_MEDIAFOLDER);
if (!is_dir(SLY_ADDONFOLDER)) mkdir(SLY_ADDONFOLDER);

// prepare our own config files
foreach (array('local', 'project') as $conf) {
	$liveFile   = $sallyRoot.'/data/config/sly_'.$conf.'.yml';
	$backupFile = $sallyRoot.'/data/config/sly_'.$conf.'.yml.bak';
	$testFile   = $sallyRoot.'/sally/tests/config/sly_'.$conf.'.yml';

	if (file_exists($liveFile)) {
		rename($liveFile, $backupFile);
	}

	copy($testFile, $liveFile);
}

// kill YAML cache
$files = glob($sallyRoot.'/data/dyn/internal/sally/yaml-cache/*');
if (is_array($files)) array_map('unlink', $files);

// load core system
require SLY_SALLYFOLDER.'/core/master.php';

// add the backend app
sly_Loader::addLoadPath(SLY_SALLYFOLDER.'/backend/lib/', 'sly_');

// init the app
$app = new sly_App_Backend();
sly_Core::setCurrentApp($app);
$app->initialize();

// make tests autoloadable
sly_Loader::addLoadPath(dirname(__FILE__).'/tests', 'sly_');

// clear current cache
sly_Core::cache()->flush('sly');

// clean up later on
register_shutdown_function(function() {
	$sallyRoot  = realpath(dirname(__FILE__).'/../../');
	$configRoot = $sallyRoot.'/data/config/';

	foreach (array('local', 'project') as $conf) {
		$liveFile   = $configRoot.'sly_'.$conf.'.yml';
		$backupFile = $configRoot.'sly_'.$conf.'.yml.bak';

		if (file_exists($backupFile)) {
			@unlink($liveFile);
			rename($backupFile, $liveFile);
			@unlink($backupFile);
		}
	}
});
