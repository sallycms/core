<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// load boot cache (frontend or backend, but never when in testing mode)
$bootcache   = SLY_DYNFOLDER.'/internal/sally/bootcache.php';
$cacheExists = SLY_IS_TESTING ? false : file_exists($bootcache);
$isPhp53     = version_compare(PHP_VERSION, '5.3', '>=');

if ($cacheExists) {
	require_once $bootcache;
}
else {
	if (!$isPhp53) {
		require_once SLY_COREFOLDER.'/lib/sly/ClassLoader.php';
		require_once SLY_COREFOLDER.'/lib/compatibility.php';

		// this is always required, but loaded by Composer (using a 'require' stmt)
		require_once SLY_COREFOLDER.'/lib/functions.php';
	}

	require_once SLY_COREFOLDER.'/lib/sly/Loader.php';
}

// the Composer autoloader should be first
if ($isPhp53) {
	require SLY_VENDORFOLDER.'/autoload.php';
}
else {
	sly_ClassLoader::getLoader(SLY_VENDORFOLDER);
}

// still load the old one, to give addOns time to update their code base
// We should remove this once we can properly handle file includes and reach
// maybe version 0.10 or 0.11.
sly_Loader::enablePathCache();
sly_Loader::register();

// cleanup
unset($bootcache, $cacheExists, $isPhp53);
