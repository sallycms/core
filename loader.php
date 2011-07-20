<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

require_once SLY_COREFOLDER.'/lib/sly/Loader.php';

sly_Loader::enablePathCache();
sly_Loader::addLoadPath(SLY_DEVELOPFOLDER.'/lib');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib/sfYaml');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib/babelcache');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib/rex/oo', 'OO');
sly_Loader::addLoadPath(SLY_COREFOLDER.'/lib/PEAR');
sly_Loader::register();

require_once SLY_COREFOLDER.'/lib/compatibility.php';
require_once SLY_COREFOLDER.'/lib/functions.php';

// Funktionen

require_once SLY_COREFOLDER.'/functions/function_rex_globals.inc.php';
require_once SLY_COREFOLDER.'/functions/function_rex_client_cache.inc.php';
require_once SLY_COREFOLDER.'/functions/function_rex_other.inc.php';
require_once SLY_COREFOLDER.'/functions/function_rex_generate.inc.php';

// register sly_Loader for cache clearing
sly_Core::dispatcher()->register('ALL_GENERATED', array('sly_Loader', 'clearCache'));
