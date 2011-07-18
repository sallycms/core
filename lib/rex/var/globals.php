<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_MODULE_ID,
 * REX_SLICE_ID,
 * REX_CTYPE_ID,
 * REX_SLOT
 *
 * @ingroup redaxo
 */
class rex_var_globals extends rex_var {
	// --------------------------------- Actions

	public function getRequestValues($REX_ACTION) {
		// Variablen hier einfügen, damit sie in einer Aktion abgefragt werden können.

		$REX_ACTION['ARTICLE_ID'] = sly_request('article_id', 'int');
		$REX_ACTION['CLANG_ID']   = sly_request('clang', 'int');
		$REX_ACTION['SLOT']       = sly_request('slot', 'string');
		$REX_ACTION['MODULE']     = sly_request('module', 'int');
		$REX_ACTION['SLICE_ID']   = sly_request('slice_id', 'int', 0);
		
		return $REX_ACTION;
	}

	public function getDatabaseValues($REX_ACTION, $slice_id) {
		$artslice = OOArticleSlice::_getSliceWhere('slice_id = '.$slice_id);
		// Variablen hier einfügen, damit sie in einer Aktion abgefragt werden können.

		if ($artslice) {
			$REX_ACTION['ARTICLE_ID'] = $artslice->getArticleId();
			$REX_ACTION['CLANG_ID']   = $artslice->getClang();
			$REX_ACTION['SLOT']       = $artslice->getSlot();
			$REX_ACTION['MODULE']     = $artslice->getModule();
			$REX_ACTION['SLICE_ID']   = $artslice->getId();
		}

		return $REX_ACTION;
	}

}
