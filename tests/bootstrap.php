<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$here      = dirname(__FILE__);
$sallyRoot = realpath($here.'/../../');

define('SLY_IS_TESTING',        true);
define('SLY_TESTING_USER_ID',   1);
define('SLY_TESTING_ROOT',      $sallyRoot);
define('SLY_TESTING_USE_CACHE', true);
define('SLY_SALLYFOLDER',       $sallyRoot.'/sally');
define('SLY_DEVELOPFOLDER',     $here.'/develop');
define('SLY_MEDIAFOLDER',       $here.'/mediapool');
define('SLY_ADDONFOLDER',       $here.'/addons');

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

// boot Sally
require SLY_TESTING_ROOT.'/sally/backend/index.php';

// make tests autoloadable
sly_Loader::addLoadPath(dirname(__FILE__).'/tests', 'sly_');

// clear current cache
sly_Core::cache()->flush('sly');
