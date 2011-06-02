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
 * Verschiebt einen Slice nach oben
 *
 * @param  int $slice_id  ID des Slices
 * @param  int $clang     ID der Sprache
 * @return array          ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSliceUp($slice_id, $clang) {
	return rex_moveSlice($slice_id, $clang, 'moveup');
}

/**
 * Verschiebt einen Slice nach unten
 *
 * @param  int $slice_id  ID des Slices
 * @param  int $clang     ID der Sprache
 * @return array          ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSliceDown($slice_id, $clang) {
	return rex_moveSlice($slice_id, $clang, 'movedown');
}

/**
 * Verschiebt einen Slice
 *
 * @param  int    $slice_id   ID des Slices
 * @param  int    $clang      ID der Sprache
 * @param  string $direction  Richtung in die verschoben werden soll
 * @return array              ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSlice($slice_id, $clang, $direction) {
	$slice_id = (int) $slice_id;
	$clang    = (int) $clang;

	if (!in_array($direction, array('moveup', 'movedown'))) {
		trigger_error('rex_moveSlice: Unsupported direction "'.$direction.'"!', E_USER_ERROR);
	}

	// slot beachten
	// verschieben / vertauschen
	// article regenerieren.

	$success = false;
	$message = t('slice_moved_error');

	// Slice finden

	$articleSlice = OOArticleSlice::getArticleSliceById($slice_id, $clang);

	if ($articleSlice) {
		$sql        = sly_DB_Persistence::getInstance();
		$article_id = (int) $articleSlice->getArticleId();
		$prior      = (int) $articleSlice->getPrior();
		$slot       = $articleSlice->getSlot();
		$newprior   = $direction == 'moveup' ? $prior - 1 : $prior + 1;
		$sliceCount = $sql->magicFetch('article_slice', 'COUNT(*)', array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot));

		if ($newprior > -1 && $newprior < $sliceCount) {
			$sql->update('article_slice', array('prior' => $prior), array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot, 'prior' => $newprior));
			$sql->update('article_slice', array('prior' => $newprior), array('id' => $slice_id));

			$message = t('slice_moved');
			$success = true;

			// Flush slice cache
			sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		}
	}

	return array($success, $message);
}

/**
 * Löscht einen Slice
 *
 * @param  int $slice_id  ID des Slices
 * @return boolean        true bei Erfolg, sonst false
 */
function rex_deleteArticleSlice($slice_id) {
	$article_slice = OOArticleSlice::getArticleSliceById($slice_id);

	if ($article_slice !== null) {
		$sql = sly_DB_Persistence::getInstance();
		$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		$sql->query('UPDATE '.$pre.'article_slice SET prior = prior -1 WHERE '.
			sprintf('article_id = %d AND clang = %d AND slot = "%s" AND prior > %d',
			$article_slice->getArticleId(), $article_slice->getClang(), $article_slice->getSlot(), $article_slice->getPrior()
		));

		$sql->delete('article_slice', array('id' => $slice_id));

		sly_Service_Factory::getSliceService()->delete(array('id' => $article_slice->getSliceId()));
		rex_deleteCacheSliceContent($article_slice->getSliceId());

		// TODO delete less entries in cache
		sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		return $sql->affectedRows() == 1;
	}

	return false;
}

/**
 * Prüft, ob ein Modul für ein bestimmtes Slice im System bekannt ist.
 *
 * @return boolean  true oder ... false
 */
function rex_slice_module_exists($sliceID, $clang) {
	$sliceID = (int) $sliceID;
	$clang   = (int) $clang;
	$slice   = OOArticleSlice::getArticleSliceById($sliceID, $clang);
	if (is_null($slice)) return false;
	$module  = $slice->getModuleName();
	return rex_module_exists($module) ? $module : false;
}

/**
 * Prüft, ob ein Modul im System bekannt ist.
 *
 * @return boolean  true oder ... false
 */
function rex_module_exists($module) {
	return sly_Service_Factory::getModuleService()->exists($module);
}

/**
 * Führt alle pre-save Aktionen eines Moduls aus
 *
 * @param  int    $module_id   ID des Moduls
 * @param  string $function    Funktion/Modus der Aktion
 * @param  array  $REX_ACTION  Array zum Speichern des Status'
 * @return array               ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_execPreSaveAction($module_id, $function, $REX_ACTION) {
	// actions are disabled because we don't have sly_module_actions in the database
	return array('', $REX_ACTION); // _rex_execSaveAction('pre', $module_id, $function, $REX_ACTION);
}

/**
 * Führt alle post-save Aktionen eines Moduls aus
 *
 * @param  int    $module_id   ID des Moduls
 * @param  string $function    Funktion/Modus der Aktion
 * @param  array  $REX_ACTION  Array zum Speichern des Status'
 * @return string              eine Meldung
 */
function rex_execPostSaveAction($module_id, $function, $REX_ACTION) {
	// actions are disabled because we don't have sly_module_actions in the database
	return array('', $REX_ACTION); // _rex_execSaveAction('post', $module_id, $function, $REX_ACTION);
}

/**
 * Führt alle X-save Aktionen eines Moduls aus
 *
 * @param  string $type        'pre' oder 'post'
 * @param  int    $module_id   ID des Moduls
 * @param  string $function    Funktion/Modus der Aktion
 * @param  array  $REX_ACTION  Array zum Speichern des Status'
 * @return string              eine Meldung
 */
function _rex_execSaveAction($type, $module_id, $function, $REX_ACTION) {
	global $REX;

	$type      = $type === 'pre' ? 'pre' : 'post';
	$module_id = (int) $module_id;
	$modebit   = rex_getActionModeBit($function);
	$message   = '';

	$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
	$sql    = sly_DB_Persistence::getInstance();

	$sql->query(
		'SELECT postsave FROM '.$prefix.'module_action ma, '.$prefix.'action a '.
		'WHERE '.$type.'save <> "" AND ma.action_id = a.id AND module_id = '.$module_id.' AND '.
		'((a.'.$type.'savemode & '.$modebit.') = '.$modebit.')'
	);

	foreach ($sql as $row) {
		$REX_ACTION['MSG'] = '';
		$iaction = reset($row);

		// replace values
		foreach (sly_Core::getVarTypes() as $obj) {
			$iaction = $obj->getACOutput($REX_ACTION, $iaction);
		}

		eval('?>'.$iaction);

		if ($REX_ACTION['MSG'] != '') {
			$message .= ' | '.$REX_ACTION['MSG'];
		}
	}

	return array($message, $REX_ACTION);
}

/**
 * Übersetzt den Modus in das dazugehörige Bitwort
 *
 * @param  string $function  Funktion/Modus der Aktion
 * @return int               ein Bitwort
 */
function rex_getActionModeBit($function) {
	if ($function == 'edit') {
		$modebit = '2'; // pre-action and edit
	}
	elseif ($function == 'delete') {
		$modebit = '4'; // pre-action and delete
	}
	else {
		$modebit = '1'; // pre-action and add
	}

	return $modebit;
}

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
function rex_copyContent($from_id, $to_id, $from_clang = 0, $to_clang = 0, $from_re_sliceid = 0, $revision = 0) {
	$from_clang      = (int) $from_clang;
	$to_clang        = (int) $to_clang;
	$from_id         = (int) $from_id;
	$to_id           = (int) $to_id;
	$from_re_sliceid = (int) $from_re_sliceid;
	$revision        = (int) $revision;

	if ($from_id == $to_id && $from_clang == $to_clang) {
		return false;
	}

	$sliceIds = OOArticleSlice::getSliceIdsForSlot($from_id, $from_clang);
	$service  = sly_Service_Factory::getSliceService();
	$sql      = sly_DB_Persistence::getInstance();
	$login    = sly_Util_User::getCurrentUser()->getLogin();

	foreach ($sliceIds as $sliceId) {
		$article_slice = OOArticleSlice::getArticleSliceById($sliceId, $from_clang);

		$slice = $service->findById($article_slice->getSliceId());
		$slice = $service->copy($slice);

		$sql->insert('article_slice', array(
			'clang'      => $to_clang,
			'slot'       => $article_slice->getSlot(),
			'prior'      => $article_slice->getPrior(),
			'slice_id'   => $slice->getId(),
			'article_id' => $to_id,
			'module'     => $slice->getModule(),
			'revision'   => 0,
			'createdate' => time(),
			'createuser' => $login
		));
	}

	// notify system
	sly_Core::dispatcher()->notify('SLY_ART_CONTENT_COPIED', null, array(
		'from_id'     => $from_id,
		'from_clang'  => $from_id,
		'to_id'       => $to_id,
		'to_clang'    => $to_clang,
		'start_slice' => $from_re_sliceid
	));

	rex_deleteCacheArticle($to_id, $to_clang);
	return true;
}

/**
 * Kopieren eines Artikels von einer Kategorie in eine andere
 *
 * @param  int $id         Artikel-ID des zu kopierenden Artikels
 * @param  int $to_cat_id  KategorieId in die der Artikel kopiert werden soll
 * @return boolean         false bei Fehler, sonst die Artikel Id des neue kopierten Artikels
 */
function rex_copyArticle($id, $target) {
	$id     = (int) $id;
	$target = (int) $target;
	$new_id = -1;
	$pos    = -1;
	$sql    = sly_DB_Persistence::getInstance();
	$cache  = sly_Core::cache();
	$login  = sly_Util_User::getCurrentUser()->getLogin();

	foreach (sly_Util_Language::findAll(true) as $clang) {
		// validate article
		$from_data = $sql->magicFetch('article', '*', array('clang' => $clang, 'id' => $id));
		if ($from_data === false) return false;

		// validate target
		if ($target === 0) {
			$to_data = array('id' => '', 'path' => '', 'name' => $from_data['name']);
		}
		else {
			$to_data = $sql->magicFetch('article', 'path, id, name', array('clang' => $clang, 'startpage' => 1, 'id' => $target));
			if ($to_data === false) return false;
		}

		// get new prior (same for all languages)
		if ($pos === -1) {
			$where = 'id = '.$target.' OR (re_id = '.$target.' AND startpage = 0)';
			$pos   = $sql->magicFetch('article', 'MAX(prior)', $where) + 1;
		}

		// get new ID (same for all languages)
		if ($new_id === -1) {
			$new_id = $sql->magicFetch('article', 'MAX(id)') + 1;
		}

		// prepare data
		$path = $to_data['path'].$to_data['id'].'|';
		$data = array(
			'id'         => $new_id,
			'clang'      => $clang,
			're_id'      => $target,
			'path'       => $path,
			'catname'    => $to_data['name'],
			'catprior'   => 0,
			'prior'      => $pos,
			'status'     => 0,
			'startpage'  => 0,
			'createdate' => time(),
			'createuser' => $login
		);

		// set all remaining fields
		$remaining = array_diff(array_keys($from_data), array_keys($data));
		foreach ($remaining as $col) $data[$col] = $from_data[$col];

		// and pump it into the database
		$sql->insert('article', $data);

		// copy slices
		rex_copyContent($id, $new_id, $clang, $clang);

		// notify system
		sly_Core::dispatcher()->notify('SLY_ART_COPIED', $id, array(
			'id'     => $new_id,
			'clang'  => $clang,
			'status' => 0,
			'name'   => $from_data['name'],
			're_id'  => $target,
			'prior'  => $pos,
			'path'   => $path,
			'type'   => $from_data['type']
		));

		$cache->delete('sly.article.list', $target.'_'.$clang.'_0');
		$cache->delete('sly.article.list', $target.'_'.$clang.'_1');
	}

	// Caches des Artikels löschen, in allen Sprachen
	// rex_deleteCacheArticle($id);

	// Caches der Kategorien löschen, da sich darin befindliche Artikel geändert haben
	rex_deleteCacheArticle($target);

	return $new_id;
}

/**
 * Verschieben eines Artikels von einer Kategorie in eine Andere
 *
 * @param  int $id      Artikel-ID des zu verschiebenden Artikels
 * @param  int $target  Kategorie-ID in die der Artikel verschoben werden soll
 * @return boolean      true bei Erfolg, sonst false
 */
function rex_moveArticle($id, $target) {
	$id      = (int) $id;
	$article = sly_Util_Article::findById($id);

	if ($article === null || $article->isStartArticle()) {
		return false;
	}

	$target = (int) $target;

	if($target !== 0 && !sly_Util_Category::exists($target)) return false;

	$source = (int) $article->getCategoryId();

	if ($source === $target) {
		return false;
	}

	$sql   = sly_DB_Persistence::getInstance();
	$cache = sly_Core::cache();
	$login = sly_Util_User::getCurrentUser()->getLogin();
	$pre   = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
	$disp  = sly_Core::dispatcher();

	//get the new prior, same for al anguages
	$sql->query('SELECT MAX(prior) as prior from '.$pre.'article WHERE id = '.$target.' OR (re_id = '.$target.' AND startpage = 0)');
	$sql->next();
	$data  = $sql->current();
	$pos   = $data['prior'] + 1;

	foreach (sly_Util_Language::findAll(true) as $clang) {
		$article = sly_Util_Article::findById($id, $clang);
		$re_id   = $target;

		if ($target === 0) {
			$path    = '|';
			$catname = $article->getName();
		}
		else {
			$targetCategory = sly_Util_Category::findById($target, $clang);
			//$to_data = $sql->magicFetch('article', 'path, name', array('clang' => $clang, 'startpage' => 1, 'id' => $target));
			$path    = $targetCategory->getPath().$target.'|';
			$catname = $targetCategory->getName();
		}

		// move article at the end of new category
		$sql->update('article', array(
			're_id'      => $re_id,
			'path'       => $path,
			'catname'    => $catname,
			'prior'      => $pos,
			'status'     => 0,
			'updatedate' => time(),
			'updateuser' => $login
		), array('id' => $id, 'clang' => $clang));

		// re-number old category
		$sql->query('UPDATE '.$pre.'article SET prior = prior - 1 WHERE re_id = '.$source.' AND startpage = 0 AND clang = '.$clang.' AND prior > '.$article->getPrior());

		// notify system
		$disp->notify('SLY_ART_MOVED', $id, array(
			'clang'  => $clang,
			'target' => $target
		));
	}

	// update cache (very generously since many articles have changed (-> been moved))
	$cache->flush('sly.article', true);

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
	foreach (array_keys($toDelete) as $id) rex_deleteCacheArticle($id);

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
