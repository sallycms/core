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
 * SLY_VALUE_VAR[1]
 *
 * @ingroup redaxo
 */
class rex_var_value extends rex_var {

	const VALUE      = 'SLY_VALUE';
	const HTML_VALUE = 'SLY_HTML_VALUE';
	const PHP_VALUE  = 'SLY_PHP_VALUE';
	const IS_VALUE   = 'SLY_IS_VALUE';
	const VALUE_VAR  = 'SLY_VALUE_VAR';

	public function getRequestValues($REX_ACTION = array()) {
		$values = sly_request('VALUE', 'array');

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
				$slice->addValue(self::VALUE, $key, var_export($value, true));
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->getOutput($REX_ACTION, $content);
		return $content;
	}

	public function getOutput($REX_ACTION, $content) {
		$content = $this->matchValue($REX_ACTION, $content);
		$content = $this->matchHtmlValue($REX_ACTION, $content);
		$content = $this->matchIsValue($REX_ACTION, $content);
		$content = $this->matchPhpValue($REX_ACTION, $content);
		$content = $this->matchVarValue($REX_ACTION, $content);

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	private function _matchValue($REX_ACTION, $content, $var, $escape = true, $nl2br = true, $escapePHP = true, $booleanize = false, $asPHPVar = false) {
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::VALUE][$id]) ? strval($REX_ACTION[self::VALUE][$id]) : '';

			if (!$asPHPVar) {
				//strip possible Quotes from string values
				$value = preg_replace("#^('*)(.*?)\\1$#si", '$2', $value);
				$value = stripslashes($value);
			}

			if ($booleanize) {
				$value = var_export(empty($value), true);
			}
			if ($escape) {
				$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			}

			if ($nl2br) {
				$value = nl2br($value);
			}

			if ($escapePHP) {
				$value = sly_Util_String::escapePHP($value);
			}

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $value, $content);
		}

		return $content;
	}

	public function matchValue($values, $content) {
		return $this->_matchValue($values, $content, self::VALUE);
	}

	private function matchHtmlValue($values, $content) {
		return $this->_matchValue($values, $content, self::HTML_VALUE, false, false, true);
	}

	private function matchPhpValue($values, $content) {
		return $this->_matchValue($values, $content, self::PHP_VALUE, false, false, false);
	}

	private function matchIsValue($values, $content) {
		return $this->_matchValue($values, $content, self::IS_VALUE, false, false, false, true);
	}
	private function matchVarValue($values, $content) {
		return $this->_matchValue($values, $content, self::VALUE_VAR, false, false, false, false, true);
	}
}
