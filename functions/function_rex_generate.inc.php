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

/**
 * Löscht die gecachten Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gelöscht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 *
 * @return void
 */
function rex_deleteCacheArticle($id, $clang = null) {
	$cache = sly_Core::cache();

	foreach (sly_Util_Language::findAll(true) as $_clang) {
		if ($clang !== null && $clang != $_clang) {
			continue;
		}

		$cache->delete('sly.article', $id.'_'.$_clang);
		$cache->delete('sly.article.list', $id.'_'.$_clang.'_0');
		$cache->delete('sly.article.list', $id.'_'.$_clang.'_1');
		$cache->delete('sly.category.list', $id.'_'.$_clang.'_0');
		$cache->delete('sly.category.list', $id.'_'.$_clang.'_1');
	}
}

/**
 * Löscht einen Artikel
 *
 * @param  int $id  ArtikelId des Artikels, der gelöscht werden soll
 * @return array    array('state' => ..., 'message' => ...)
 */
function rex_deleteArticle($id) {
	// Artikel löschen
	//
	// Kontrolle ob Erlaubnis nicht hier.. muss vorher geschehen
	//
	// -> startpage = 0
	// --> Artikelfiles löschen
	// ---> article
	// ---> content
	// ---> clist
	// ---> alist
	// -> startpage = 1
	// --> rekursiv aufrufen

	$config = sly_Core::config();
	$return = array();
	$return['state'] = false;

	if ($id == $config->get('START_ARTICLE_ID')) {
		$return['message'] = t('cant_delete_sitestartarticle');
		return $return;
	}

	if ($id == $config->get('NOTFOUND_ARTICLE_ID')) {
		$return['message'] = t('cant_delete_notfoundarticle');
		return $return;
	}

	$clang       = sly_Core::getCurrentClang();
	$sql         = sly_DB_Persistence::getInstance();
	$articleData = $sql->magicFetch('article', 're_id, startpage', compact('id', 'clang'));

	if ($articleData !== false) {
		$re_id = (int) $articleData['re_id'];
		$return['state'] = true;

		if ($articleData['startpage'] == 1) {
			$return['message'] = t('category_deleted');
		}
		else {
			$return['message'] = t('article_deleted');
		}

		// Rekursion über alle Kindkategorien ergab keine Fehler
		// => löschen erlaubt

		if ($return['state'] === true) {
			rex_deleteCacheArticle($id);

			$sql = sly_DB_Persistence::getInstance();
			$sql->delete('article', array('id' => $id));
			$sql->delete('article_slice', array('article_id' => $id));
		}

		return $return;
	}

	$return['message'] = t('category_doesnt_exist');
	return $return;
}

/**
 * Kopiert eine Ordner von $srcdir nach $dstdir
 *
 * @deprecated
 * @param  string $srcdir    Zu kopierendes Verzeichnis
 * @param  string $dstdir    Zielpfad
 * @return bool              true bei Erfolg, false bei Fehler
 */
function rex_copyDir($srcdir, $dstdir) {
	$srcdir = new sly_Util_Directory($srcdir);
	return $srcdir->copyTo($dstdir);
}
