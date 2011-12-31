<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('SLY_IS_TESTING', true);     ///< boolean  für Testläufe muss hier true stehen.
define('SLY_TESTING_USER_ID', 1);   ///< int      die ID des Users, der eingeloggt sein soll

$sallyRoot = realpath(dirname(__FILE__).'/../');
define('SLY_TESTING_ROOT', $sallyRoot);

require SLY_TESTING_ROOT.'/sally/index.php';
