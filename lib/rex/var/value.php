<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * SLY_VALUE[1],
 * SLY_HTML_VALUE[1],
 * SLY_PHP_VALUE[1],
 * SLY_IS_VALUE[1]
 *
 * @ingroup redaxo
 */
class rex_var_value extends rex_var {

	const VALUE      = 'SLY_VALUE';
	const HTML_VALUE = 'SLY_HTML_VALUE';
	const PHP_VALUE  = 'SLY_PHP_VALUE';
	const IS_VALUE   = 'SLY_IS_VALUE';

	public function getRequestValues($REX_ACTION) {
		$values = sly_requestArray('VALUE', 'string');

		foreach ($values as $key => $value) {
			$REX_ACTION[self::VALUE][$key] = $value;
		}

		return $REX_ACTION;
	}

	public function getDatabaseValues($slice_id) {
		$values = sly_Service_Factory::getSliceValueService()->find(array('slice_id' => $slice_id, 'type' => 'SLY_VALUE'));
		$data   = array();

		foreach ($values as $value) {
			$data[self::VALUE][$value->getFinder()] = $value->getValue();
		}

		return $data;
	}

	public function setSliceValues($REX_ACTION, $slice_id) {
		$slice = sly_Service_Factory::getSliceService()->findById($slice_id);
		if (isset($REX_ACTION[self::VALUE])) {
			foreach ($REX_ACTION[self::VALUE] as $key => $value){
				$slice->addValue(self::VALUE, $key, $value);
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->getOutput($REX_ACTION, $content);
		return $content;
	}

	public function getOutput($REX_ACTION, $content) {
		$content = $this->matchValue($REX_ACTION, $content, true);
		$content = $this->matchHtmlValue($REX_ACTION, $content);
		$content = $this->matchIsValue($REX_ACTION, $content);
		$content = $this->matchPhpValue($REX_ACTION, $content);

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	private function _matchValue($REX_ACTION, $content, $var, $escape = false, $nl2br = false, $stripPHP = false, $booleanize = false) {
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$replace = isset($REX_ACTION[self::VALUE][$id]) ? strval($REX_ACTION[self::VALUE][$id]) : '';

			if ($booleanize) {
				$replace = empty($replace);
			}
			else {
				if ($escape) {
					$replace = htmlspecialchars($replace, ENT_QUOTES, 'UTF-8');
				}

				if ($nl2br) {
					$replace = nl2br($replace);
				}

				if ($stripPHP) {
					$replace = sly_Util_String::escapePHP($replace);
				}

				$replace = $this->handleGlobalVarParams($var, $args, $replace);
				$content = str_replace($var.'['.$param_str.']', $replace, $content);
			}
		}

		return $content;
	}

	public function matchValue($slice_id, $content, $nl2br = false) {
		return $this->_matchValue($slice_id, $content, self::VALUE, true, $nl2br);
	}

	private function matchHtmlValue($slice_id, $content) {
		return $this->_matchValue($slice_id, $content, self::HTML_VALUE, false, false, true);
	}

	private function matchPhpValue($slice_id, $content) {
		return $this->_matchValue($slice_id, $content, self::PHP_VALUE, false, false, false);
	}

	private function matchIsValue($slice_id, $content) {
		return $this->_matchValue($slice_id, $content, self::IS_VALUE, false, false, false, true);
	}
}
