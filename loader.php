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

if ($cacheExists) {
	require_once $bootcache;
}
else {
	require_once SLY_COREFOLDER.'/lib/sly/ClassLoader.php';
	require_once SLY_COREFOLDER.'/lib/sly/Loader.php';
	require_once SLY_COREFOLDER.'/lib/compatibility.php';
	require_once SLY_COREFOLDER.'/lib/functions.php';
}

// the Composer autoloader should be first
// since Composer still requires __DIR__, this is only used if possible (yet)
if (version_compare(PHP_VERSION, '5.3', '>=')) {
	sly_ClassLoader::getLoader(SLY_VENDORFOLDER);
}

sly_Loader::enablePathCache();
sly_Loader::addLoadPath(SLY_DEVELOPFOLDER.'/lib');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib');
sly_Loader::addLoadPath(SLY_VENDORFOLDER.'/fabpot/yaml/lib');
sly_Loader::addLoadPath(SLY_VENDORFOLDER.'/webvariants/babelcache');
sly_Loader::register();

// cleanup
unset($bootcache, $cacheExists);
