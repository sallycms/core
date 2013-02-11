<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// load boot cache
$bootcache   = SLY_DYNFOLDER.'/internal/sally/bootcache.php';
$cacheExists = file_exists($bootcache);

if ($cacheExists) {
	require_once $bootcache;
}

// init the Composer autoloader
// In case we're running through PHPUnit, the Composer autoloader has already
// been loaded. Since we have a "files" declaration, we cannot include the
// functions.php twice without errors. So we have to skip loading our own
// compatibility loader and simply use the original one. This means that in
// test mode, classes with leading underscores are not possible without
// classmaps.
if (!class_exists('Composer\Autoload\ClassLoader', false)) {
	$loader = require SLY_VENDORFOLDER.'/autoload_52.php';
	$loader->setAllowUnderscore(true);
}
else {
	$loader = require SLY_VENDORFOLDER.'/autoload.php';
}

// still load the old one, to give addOns time to update their code base
// We should remove this once we can properly handle file includes and reach
// maybe version 0.10 or 0.11.
sly_Loader::enablePathCache();
sly_Loader::register();

// cleanup
unset($bootcache, $cacheExists);

return $loader;
