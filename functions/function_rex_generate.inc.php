<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Funktionensammlung für die generierung der
 * Artikel/Templates/Kategorien/Metainfos.. etc.
 *
 * @package redaxo4
 */

// ----------------------------------------- Alles generieren
// (heißt in REDAXO-Sprech: Alles löschen....)

/**
 * Löscht den vollständigen Artikel-Cache.
 */
function rex_generateAll() {
	rex_deleteDir(SLY_DYNFOLDER.'/internal/sally/article_slice', false);
	rex_deleteDir(SLY_DYNFOLDER.'/internal/sally/templates', false);
	rex_deleteDir(SLY_DYNFOLDER.'/internal/sally/files', false);

	sly_Core::cache()->flush('sly', true);

	$MSG = t('delete_cache_message');
	$MSG = sly_Core::dispatcher()->filter('ALL_GENERATED', $MSG);

	return $MSG;
}

// ----------------------------------------- ARTICLE

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
 *
 * @deprecated
 */
function rex_deleteCacheSliceContent($slice_id) {
	sly_Util_Slice::clearSliceCache($slice_id);
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
 * Löscht einen Ordner/Datei mit Unterordnern
 *
 * @param  string $file            Zu löschender Ordner/Datei
 * @param  bool   $delete_folders  Ordner auch löschen? false => nein, true => ja
 * @param  bool   $isRecursion     wird beim rekursiven Aufruf auf true gesetzt, um zu vermeiden, immer wieder das Error-Reporting auf 0 zu setzen
 * @return bool                    true bei Erfolg, sonst false
 */
function rex_deleteDir($file, $delete_folders = false, $isRecursion = false) {
	$state = true;
	$level = $isRecursion ? -1 : error_reporting(0);
	$file  = rtrim($file, '/\\');

	if (file_exists($file)) {
		if (is_dir($file)) {
			$handle = opendir($file);

			if (!$handle) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}

			while ($filename = readdir($handle)) {
				if ($filename == '.' || $filename == '..') {
					continue;
				}

				$full = $file.DIRECTORY_SEPARATOR.$filename;

				// Auch wenn wir beim rekursiven Aufruf eine einzelne Datei löschen
				// würden, sparen wir uns den Aufwand und erledigen es gleich mit.

				if (is_dir($full) && !rex_deleteDir($full, $delete_folders, true)) {
					$state = false;
				}

				if (is_file($full) && !unlink($full)) {
					$state = false;
				}
			}

			closedir($handle);

			if ($state !== true) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}

			// Ordner auch löschen?

			if ($delete_folders && !rmdir($file)) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}
		}
		else {
			// Datei löschen

			if (!unlink($file)) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}
		}
	}
	else {
		// Datei/Ordner existiert nicht

		if (!$isRecursion) error_reporting($level);
		return false;
	}

	if (!$isRecursion) error_reporting($level);
	return true;
}

/**
 * Lösch allen Datei in einem Ordner
 *
 * @deprecated
 * @param  string $file  Pfad zum Ordner
 * @return bool          true bei Erfolg, sonst false
 */
function rex_deleteFiles($directory) {
	$directory = new sly_Util_Directory($directory);
	return $directory->deleteFiles();
}

/**
 * Kopiert eine Ordner von $srcdir nach $dstdir
 *
 * @param  string $srcdir    Zu kopierendes Verzeichnis
 * @param  string $dstdir    Zielpfad
 * @return bool              true bei Erfolg, false bei Fehler
 */
function rex_copyDir($srcdir, $dstdir) {
	$state = true;

	if (!is_dir($dstdir)) {
		sly_Util_Directory::create($dstdir);
	}

	if ($curdir = opendir($srcdir)) {
		while ($file = readdir($curdir)) {
			if ($file[0] != '.') {
				$srcfile = $srcdir.DIRECTORY_SEPARATOR.$file;
				$dstfile = $dstdir.DIRECTORY_SEPARATOR.$file;

				if (is_file($srcfile)) {
					$isNewer = true;

					if (is_file($dstfile)) {
						$isNewer = (filemtime($srcfile) - filemtime($dstfile)) > 0;
					}

					if ($isNewer) {
						if (copy($srcfile, $dstfile)) {
							touch($dstfile, filemtime($srcfile));
							chmod($dstfile, 0777);
						}
						else {
							return false;
						}
					}
				}
				elseif (is_dir($srcfile)) {
					$state &= rex_copyDir($srcfile, $dstfile);
				}
			}
		}

		closedir($curdir);
	}

	return $state;
}
