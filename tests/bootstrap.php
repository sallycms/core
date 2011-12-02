<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$sallyRoot = realpath(dirname(__FILE__).'/../../');

define('SLY_IS_TESTING', true);
define('SLY_TESTING_USER_ID', 1);
define('SLY_TESTING_ROOT', $sallyRoot);

// prepare our own config files
foreach (array('local', 'project') as $conf) {
	$liveFile   = $sallyRoot.'/sally/data/config/sly_'.$conf.'.yml';
	$backupFile = $sallyRoot.'/sally/data/config/sly_'.$conf.'.yml.bak';
	$testFile   = $sallyRoot.'/sally/tests/config/sly_'.$conf.'.yml';

	if (file_exists($liveFile)) {
		rename($liveFile, $backupFile);
	}

	copy($testFile, $liveFile);
}

// make sure develop contents doesn't distract us
// (mainly useful on installations that are also used for developing and not just for testing)
$developDir = $sallyRoot.'/develop';
if (is_dir($developDir)) rename($developDir, $developDir.'_tmp');

// boot Sally
require SLY_TESTING_ROOT.'/sally/backend/index.php';

// make tests autoloadable
sly_Loader::addLoadPath(dirname(__FILE__).'/tests', 'sly_');
require_once SLY_COREFOLDER.'/functions/function_rex_content.inc.php';
