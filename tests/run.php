<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

// gotta love Composer...
$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('', __DIR__.'/../tests/tests');

// we must load phpunit's Autoload.php in order to have phpunit_autoload() and
// others defined. Else phpunit will fail running the selenium tests:
// Fatal error: Call to undefined function phpunit_autoload() in ~/src/vendor/pear-pear.phpunit.de/PHPUnit/PHPUnit/Util/GlobalState.php on line 377
require_once __DIR__.'/../vendor/pear-pear.phpunit.de/PHPUnit/PHPUnit/Autoload.php';

PHPUnit_TextUI_Command::main();
