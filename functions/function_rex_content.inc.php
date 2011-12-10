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
 * Konvertiert einen Artikel zum Startartikel der eigenen Kategorie
 *
 * @param  int $neu_id  Artikel ID des Artikels, der Startartikel werden soll
 * @return boolean      true bei Erfolg, sonst false
 */
function rex_article2startpage($neu_id) {
	$neu_id = (int) $neu_id;
	$sql    = sly_DB_Persistence::getInstance();

	// neuer Startartikel

	$neu = $sql->magicFetch('article', 'path, re_id', 'id = '.$neu_id.' AND startpage = 0 AND clang = 1 AND re_id <> 0');

	if ($neu === false) {
		return false;
	}

	$neu_path   = $neu['path'];
	$neu_cat_id = (int) $neu['re_id'];

	// alter Startartikel

	$alt_id = $neu_cat_id;
	$alt    = $sql->magicFetch('article', 'path', array('id' => $alt_id, 'startpage' => 1, 'clang' => 1));

	if ($alt === false) {
		return false;
	}

	// Diese Felder werden von den beiden Artikeln ausgetauscht.

	$params = array('id', 'path', 'catname', 'startpage', 'catprior', 'status', 're_id');
	$select = implode(',', $params);
	$cache  = sly_Core::cache();

	foreach (sly_Util_Language::findAll(true) as $clang) {
		$sql->select('article', $select, array('clang' => $clang, 'id' => array($neu_cat_id, $neu_id)));

		foreach ($sql as $row) {
			$id = $row['id'];
			unset($row['id']);
			$data[$id] = $row;
		}

		// overwrite re_id of new start article
		$data[$neu_id]['re_id'] = $neu_id;

		// update old start article
		$sql->update('article', $data[$neu_id], array('id' => $alt_id, 'clang' => $clang));

		// update new start article
		$sql->update('article', $data[$neu_cat_id], array('id' => $neu_id, 'clang' => $clang));

		// update cache
		$cache->delete('sly.article', $neu_id.'_'.$clang);
		$cache->delete('sly.article', $alt_id.'_'.$clang);
		$cache->delete('sly.category', $alt_id.'_'.$clang);
		$cache->delete('sly.article.list', $alt_id.'_'.$clang.'_0');
		$cache->delete('sly.article.list', $alt_id.'_'.$clang.'_1');
		$cache->delete('sly.category.list', $data[$neu_cat_id]['re_id'].'_'.$clang.'_0');
		$cache->delete('sly.category.list', $data[$neu_cat_id]['re_id'].'_'.$clang.'_1');
	}

	// switch parent id and adjust paths
	$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

	$sql->update('article', array('re_id' => $neu_id), array('re_id' => $alt_id));
	$sql->query('UPDATE '.$prefix.'article SET path = REPLACE(path, "|'.$alt_id.'|", "|'.$neu_id.'|") WHERE path LIKE "%|'.$alt_id.'|%"');

	// notify system
	sly_Core::dispatcher()->notify('SLY_ART_TO_STARTPAGE', $neu_id, array('old_cat' => $alt_id));

  	return true;
}

/**
 * Kopiert die Inhalte eines Artikels in einen anderen Artikel
 *
 * @param  int $from_id          Artikel-ID des Artikels, aus dem kopiert werden (Quell Artikel-ID)
 * @param  int $to_id            Artikel-ID des Artikel, in den kopiert werden sollen (Ziel Artikel-ID)
 * @param  int $from_clang       Sprach-ID des Artikels, aus dem kopiert werden soll (Quell Sprach-ID)
 * @param  int $to_clang         Sprach-ID des Artikels, in den kopiert werden soll (Ziel Sprach-ID)
 * @param  int $from_re_sliceid  ID des Slices, bei dem begonnen werden soll
 * @return boolean               true bei Erfolg, sonst false
 */
function rex_copyContent($from_id, $to_id, $from_clang = 0, $to_clang = 0, $revision = 0) {
	$from_clang = (int) $from_clang;
	$to_clang   = (int) $to_clang;
	$from_id    = (int) $from_id;
	$to_id      = (int) $to_id;
	$revision   = (int) $revision;

	if ($from_id == $to_id && $from_clang == $to_clang) {
		return false;
	}

	$sliceService        = sly_Service_Factory::getSliceService();
	$articleSliceService = sly_Service_Factory::getArticleSliceService();
	$articleSlices       = $articleSliceService->find(array('article_id' => $from_id, 'clang' => $from_clang, 'revision' => $revision));
	$sql                 = sly_DB_Persistence::getInstance();
	$login               = sly_Util_User::getCurrentUser()->getLogin();

	foreach ($articleSlices as $articleSlice) {
		$sql->beginTransaction();
		$slice = $articleSlice->getSlice();
		$slice = $sliceService->copy($slice);

		$articleSliceService->create(array(
			'clang'      => $to_clang,
			'slot'       => $articleSlice->getSlot(),
			'prior'      => $articleSlice->getPrior(),
			'slice_id'   => $slice->getId(),
			'article_id' => $to_id,
			'revision'   => 0,
			'createdate' => time(),
			'createuser' => $login,
			'updatedate' => time(),
			'updateuser' => $login
		));
		$sql->commit();
	}

	// notify system
	sly_Core::dispatcher()->notify('SLY_ART_CONTENT_COPIED', null, array(
		'from_id'     => $from_id,
		'from_clang'  => $from_id,
		'to_id'       => $to_id,
		'to_clang'    => $to_clang,
	));

	sly_Service_Factory::getArticleService()->deleteCache($to_id, $to_clang);

	return true;
}

/**
 * Verschieben einer Kategorie in eine andere
 *
 * @param  int $from_cat  Kategorie-ID der Kategorie, die verschoben werden soll (Quelle)
 * @param  int $to_cat    Kategorie-ID der Kategorie, in die verschoben werden soll (Ziel)
 * @return boolean        true bei Erfolg, sonst false
 */
function rex_moveCategory($from_cat, $to_cat) {
	$from_cat = (int) $from_cat;
	$to_cat   = (int) $to_cat;
	$prefix   = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
	$sql      = sly_DB_Persistence::getInstance();
	$cache    = sly_Core::cache();
	$login    = sly_Util_User::getCurrentUser()->getLogin();

	if ($from_cat == $to_cat) {
		return false;
	}

	// Kategorien vorhanden?

	$from_data = $sql->magicFetch('article', 'path, re_id', array('startpage' => 1, 'id' => $from_cat, 'clang' => 1));
	$to_data   = $to_cat == 0 ? false : $sql->magicFetch('article', 'path, re_id', array('startpage' => 1, 'id' => $to_cat, 'clang' => 1));

	if (!$from_data || (!$to_data && $to_cat !== 0)) {
		// eine der Kategorien existiert nicht
		return false;
	}

	$oldParent = $from_data['re_id'];

	// Ist die Zielkategorie im Pfad der Quellkategorie?

	if ($to_cat > 0) {
		$tcats = explode('|', $to_data['path']);

		if (in_array($from_cat, $tcats)) {
			// Zielkategorie ist in Quellkategorie -> nicht verschiebbar
			return false;
		}
	}

	// folgende cats regenerate

	$toDelete[$oldParent] = 1;
	$toDelete[$from_cat]  = 1;
	$toDelete[$to_cat]    = 1;

	if ($to_cat > 0) {
		$to_path  = $to_data['path'].$to_cat.'|';
		$to_re_id = $to_data['re_id'];
	}
	else {
		$to_path  = '|';
		$to_re_id = 0;
	}

	// update paths

	$from_path = $from_data['path'].$from_cat.'|';
	$cats      = array();

	$sql->query('SELECT id, path FROM '.$prefix.'article WHERE path LIKE "'.$from_path.'%" AND clang = 1');

	foreach ($sql as $row) {
		$cats[$row['id']] = $row['path'];
	}

	foreach ($cats as $id => $path) {
		// update
		$new_path = $to_path.$from_cat.'|'.str_replace($from_path, '', $path);
		$sql->update('article', array('path' => $new_path), array('id' => $id));

		// cat in gen eintragen
		$toDelete[$id] = 1;
	}

	// clang holen, max catprio holen und entsprechend updaten

	foreach (sly_Util_Language::findAll(true) as $clang) {
		$catprior = (int) $sql->magicFetch('article', 'catprior', array('id' => $from_cat, 'clang' => $clang));
		$max      = (int) $sql->magicFetch('article', 'MAX(catprior)', array('re_id' => $to_cat, 'clang' => $clang));

		// update the category itself
		$sql->update('article', array('path' => $to_path, 're_id' => $to_cat, 'catprior' => $max + 1), array('id' => $from_cat, 'clang' => $clang));

		// move the remaining categories up
		$sql->query('UPDATE '.$prefix.'article SET catprior = catprior - 1 WHERE re_id = '.$oldParent.' AND startpage = 1 AND clang = '.$clang.' AND catprior > '.$catprior);
	}

	// update cache (very generously since many categories have changed (-> been moved))
	$cache->flush('sly.category', true);

	// generiere Artikel neu - ohne neue Inhaltsgenerierung
	$service = sly_Service_Factory::getArticleService();
	foreach (array_keys($toDelete) as $id) $service->deleteCache($id);

	// notify system
	$dispatcher = sly_Core::dispatcher();

	foreach (sly_Util_Language::findAll(true) as $clang) {
		$dispatcher->notify('SLY_CAT_MOVED', $from_cat, array(
			'clang'  => $clang,
			'target' => $to_cat
		));
	}

	return true;
}
