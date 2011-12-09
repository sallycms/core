<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

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
