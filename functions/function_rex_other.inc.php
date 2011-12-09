<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Sonstige Funktionen
 *
 * @package redaxo4
 */

/**
 * Übersetzt den text $text, falls dieser mit dem prefix "translate:" beginnt.
 *
 * @param  string    $text     der zu übersetzende Text
 * @param  sly_I18N  $i18n
 * @param  bool      $as_html  wenn true, wird das Ergebnis durch sly_html() behandelt
 * @return string              der übersetzte Wert
 */
function rex_translate($text, sly_I18N $i18n = null, $as_html = true) {
	$transKey = 'translate:';

	if (sly_Util_String::startsWith($text, $transKey)) {
		if (!$i18n) {
			$i18n = sly_Core::getI18N();
		}
		$text = $i18n->msg(mb_substr($text, mb_strlen($transKey)));
	}

	return $as_html ? sly_html($text) : $text;
}
