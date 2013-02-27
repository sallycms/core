<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// check if we're installed as a dependency, residing in sally/core/
$vendor = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'vendor';
/*
if (!file_exists($vendor.'/autoload_52.php')) {
	// check if we're a standalone installation
	$vendor = dirname(__FILE__).DIRECTORY_SEPARATOR.'vendor';

	if (!file_exists($vendor.'/autoload_52.php')) {
		print
			'You must set up the project dependencies, run the following commands:'.PHP_EOL.
			'php composer.phar install'.PHP_EOL;
		exit(1);
	}
}*/

// init the Composer autoloader
$loader = require $vendor.'/autoload_52.php';
$loader->setAllowUnderscore(true);

// make sure to use develop/lib as the first load path
$loader->add('', dirname(dirname(dirname(__FILE__))).'/develop/lib', true);

// still load the old one, to give addOns time to update their code base
// We should remove this once we can properly handle file includes and reach
// maybe version 0.10 or 0.11.
sly_Loader::enablePathCache();
sly_Loader::register();

// cleanup
unset($vendor);

return $loader;
