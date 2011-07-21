<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_ARTICLE[1]
 * REX_ARTICLE[id=1]
 *
 * REX_ARTICLE[id=1 slot=2 clang=1] or REX_ARTICLE[id=1 ctype=2 clang=1]
 *
 * @ingroup redaxo
 */
class rex_var_article extends rex_var {
	// --------------------------------- Output

	public function getTemplate($content) {
		return $this->matchArticle($content, true);
	}

	protected function handleDefaultParam($varname, $args, $name, $value) {
		switch ($name) {
			case '1':
			case 'clang':
				$args['clang'] = (int) $value;
				break;

			case '2':
			case 'slot':
			case 'ctype':
				$args['slot'] = $value;
				break;
		}

		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchArticle($content, $replaceInTemplate = false) {
		$var     = 'REX_ARTICLE';
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args)  = $match;
			list ($article_id, $args) = $this->extractArg('id',    $args, 0);
			list ($clang, $args)      = $this->extractArg('clang', $args, 'sly_Core::getCurrentClang()');
			list ($slot,  $args)      = $this->extractArg('slot',  $args, '');

			$tpl = '';

			if ($article_id == 0) {
				// REX_ARTICLE[] keine id -> aktuellen artikel verwenden
				if ($replaceInTemplate) {
					// aktueller Artikel darf nur in Templates, nicht in Modulen eingebunden werden
					// => endlossschleife
					$tpl = '<?php print '.$this->handleGlobalVarParamsSerialized($var, $args, '$this->getArticle(\''.$slot.'\')').'; ?>';
				}
			}
			elseif ($article_id > 0) {
				// REX_ARTICLE[id=5] kompletten artikel mit gegebener artikel id einbinden
				// bezeichner wählen, der keine variablen aus modulen/templates überschreibt
				$varname = '$__rex_art';
				$tpl     =
					'<?php '.
					$varname .' = sly_Util_Article::findById('.$article_id.', '.$clang.'); '.
					'print '.$this->handleGlobalVarParamsSerialized($var, $args, $varname.'->getArticle(\''.$slot.'\')').'; ?>';
			}

			if ($tpl) {
				$content = str_replace($var.'['.$param_str.']', $tpl, $content);
			}
		}

		return $content;
	}
}
