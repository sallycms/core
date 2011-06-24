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

	public function getACRequestValues($REX_ACTION) {
		// SLICE ID im Update Mode setzen
		if ($this->isEditEvent()) {
			$REX_ACTION['EVENT']    = 'EDIT';
			$REX_ACTION['SLICE_ID'] = sly_request('slice_id', 'int');
		}

		// SLICE ID im Delete Mode setzen
		elseif ($this->isDeleteEvent()) {
			$REX_ACTION['EVENT']    = 'DELETE';
			$REX_ACTION['SLICE_ID'] = sly_request('slice_id', 'int');
		}

		// Im Add Mode 0 setze wg auto-increment
		else {
			$REX_ACTION['EVENT']    = 'ADD';
			$REX_ACTION['SLICE_ID'] = 0;
		}

		// Variablen hier einfügen, damit sie in einer Aktion abgefragt werden können.

		$REX_ACTION['ARTICLE_ID'] = sly_request('article_id', 'int');
		$REX_ACTION['CLANG_ID']   = sly_request('clang', 'int');
		$REX_ACTION['CTYPE_ID']   = sly_request('slot', 'string');
		$REX_ACTION['SLOT']       = sly_request('slot', 'string');
		$REX_ACTION['MODULE_ID']  = sly_request('module_id', 'int');

		return $REX_ACTION;
	}

	public function getACDatabaseValues($REX_ACTION, $slice_id) {
		$artslice = OOArticleSlice::_getSliceWhere('slice_id = '.$slice_id);
		$slice    = sly_Service_Factory::getSliceService()->findById($slice_id);

		// Variablen hier einfügen, damit sie in einer Aktion abgefragt werden können.

		if ($artslice && $slice) {
			$REX_ACTION['ARTICLE_ID'] = $artslice->getArticleId();
			$REX_ACTION['CLANG_ID']   = $artslice->getClang();
			$REX_ACTION['CTYPE_ID']   = $artslice->getSlot();
			$REX_ACTION['SLOT']       = $artslice->getSlot();
			$REX_ACTION['MODULE']     = $slice->getModule();
			$REX_ACTION['SLICE_ID']   = $artslice->getId();
		}

		return $REX_ACTION;
	}

	public function setACValues($slice_id, $REX_ACTION, $escape = false, $prependTableName = true) {
//		$this->setValue($sql, 'id', $REX_ACTION['SLICE_ID'], $escape, $prependTableName);
//		$this->setValue($sql, 'ctype', $REX_ACTION['CTYPE_ID'], $escape, $prependTableName);
//		$this->setValue($sql, 'modultyp_id', $REX_ACTION['MODULE_ID'], $escape, $prependTableName);
	}

	// --------------------------------- Output

	public function getBEOutput($slice_id, $content) {
		return $content;
	}
}
