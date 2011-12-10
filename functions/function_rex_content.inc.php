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
