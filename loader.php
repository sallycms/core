<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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

// load PHP <5.3 compat functions
require_once SLY_COREFOLDER.'/lib/compatibility.php';

// init the Composer autoloader
$loader = require SLY_VENDORFOLDER.'/autoload_52.php';

// still load the old one, to give addOns time to update their code base
// We should remove this once we can properly handle file includes and reach
// maybe version 0.10 or 0.11.
sly_Loader::enablePathCache();
sly_Loader::register();

// cleanup
unset($bootcache, $cacheExists);

return $loader;
