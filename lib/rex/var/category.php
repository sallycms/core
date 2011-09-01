<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * SLY_CATEGORY[xzy]
 * SLY_CATEGORY[field=xzy]
 * SLY_CATEGORY[field=xzy id=3]
 * SLY_CATEGORY[field=xzy id=3 clang=2]
 *
 * @ingroup redaxo
 */
class rex_var_category extends rex_var {
	// --------------------------------- Output

	public function getTemplate($content) {
		return $this->matchCategory($content, true);
	}

	protected function handleDefaultParam($varname, $args, $name, $value) {
		if ($name == 'field') $args['field'] = (string) $value;
		if ($name == 'clang') $args['clang'] = (int) $value;

		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchCategory($content, $replaceInTemplate = false) {
		$var     = 'SLY_CATEGORY';
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args)   = $match;
			list ($category_id, $args) = $this->extractArg('id',    $args, 0);
			list ($clang, $args)       = $this->extractArg('clang', $args, 'sly_Core::getCurrentClang()');
			list ($field, $args)       = $this->extractArg('field', $args, '');

			$tpl = '';

			if ($category_id == 0) {
				// REX_CATEGORY[field=name] feld von aktueller kategorie verwenden
				if (OOCategory::hasValue($field)) {
					// bezeichner wählen, der keine variablen
					// aus modulen/templates überschreibt
					// beachte: root-artikel haben keine kategorie
					$varname_art = '$__rex_art';
					$varname_cat = '$__rex_cat';
					$tpl         =
						'<?php '.
						$varname_art.' = sly_Core::getCurrentArticle('.$clang.'); '.
						$varname_cat.' = '.$varname_art.' ? '.$varname_art.'->getCategory() : null; '.
						'if ('.$varname_cat.') print sly_html('.$this->handleGlobalVarParamsSerialized($var, $args, $varname_cat.'->getValue(\''.addslashes($field).'\')').'); ?>';
				}
			}
			else if ($category_id > 0) {
				// SLY_CATEGORY[field=name id=5] feld von gegebene category_id verwenden
				if ($field && OOCategory::hasValue($field)) {
					// bezeichner wählen, der keine variablen
					// aus modulen/templates überschreibt
					$varname = '$__rex_cat';
					$tpl     =
						'<?php '.
						$varname.' = sly_Util_Category::findById('.$category_id.', '.$clang.'); '.
						'if ('.$varname.') print sly_html('.$this->handleGlobalVarParamsSerialized($var, $args, $varname.'->getValue(\''.addslashes($field).'\')').'); ?>';
				}
			}

			if ($tpl) {
				$content = str_replace($var.'['.$param_str.']', $tpl, $content);
			}
		}

		return $content;
	}
}
