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
$configRoot = $sallyRoot.'/sally/data/config/';

foreach (array('local', 'project') as $conf) {
	$liveFile   = $configRoot.'sly_'.$conf.'.yml';
	$backupFile = $configRoot.'sly_'.$conf.'.yml.bak';

	if (file_exists($backupFile)) {
		@unlink($liveFile);
		rename($backupFile, $liveFile);
		@unlink($backupFile);
	}
}

$developDir = $sallyRoot.'/develop';

if (is_dir($developDir.'_tmp')) {
	if (is_dir($developDir)) {
		// cleanup a possibly generated develop directory
		$iterator = new RecursiveDirectoryIterator($developDir);
		$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $file) {
			$path = $file->getPathname();
			$base = basename($path);

			if ($base === '.' || $base === '..') continue;
			$file->isDir() ? rmdir($path) : unlink($path);
		}

		rmdir($developDir);
		unset($iterator);
	}

	// and finally rename the original one
	rename($developDir.'_tmp', $developDir);
}
