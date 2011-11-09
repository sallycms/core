<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */

/**
 * Löscht den vollständigen Artikel-Cache.
 */
function rex_generateAll() {
	clearstatcache();

	foreach (array('article_slice', 'templates') as $dir) {
		$obj = new sly_Util_Directory(SLY_DYNFOLDER.'/internal/sally/'.$dir);
		$obj->deleteFiles();
	}

	sly_Core::cache()->flush('sly', true);

	// create bootcache
	sly_Util_BootCache::recreate('frontend');
	sly_Util_BootCache::recreate('backend');

	return sly_Core::dispatcher()->filter('ALL_GENERATED', t('delete_cache_message'));
}
