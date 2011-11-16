<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Abstrakte Basisklasse für REX_VARS
 *
 * @ingroup redaxo
 */
abstract class rex_var {

	/**
	 * Actionmethode:
	 * Zum füllen des sql aus dem $REX_ACTION Array
	 */
	public function setSliceValues($REX_ACTION, $slice_id) {
		// do nothing
	}

	/**
	 * Actionmethode:
	 * Zum füllen des $REX_ACTION Arrays aus den Input Formularwerten
	 * @return REX_ACTION Array
	 */
	public function getRequestValues($REX_ACTION = array()) {
		return $REX_ACTION;
	}

	/**
	 * Actionmethode:
	 * Zum Füllen des $REX_ACTION Arrays aus der Datenbank (rex_sql)
	 * @return REX_ACTION Array
	 */
	public function getDatabaseValues($slice_id) {
		return array();
	}

	/**
	 * Actionmethode:
	 * Ersetzen der Werte in dem Aktionsscript
	 * @return output String
	 */
	public function getOutput($REX_ACTION, $content) {
		return $content;
	}

	// --------------------------------- Ouput

	/**
	 * Ausgabe eines Modules im Backend bei der Eingabe
	 * sql Objekt mit der passenden Slice
	 *
	 * BE = Backend
	 */
	public function getBEInput($sql, $content) {
		return $this->getOutput($sql, $content);
	}

	/**
	 * Ausgabe eines Templates
	 */
	public function getTemplate($content) {
		return $content;
	}

	/**
	 * Callback um nicht explizit gehandelte OutputParameter zu behandeln
	 */
	protected function handleDefaultParam($varname, $args, $name, $value) {
		if ($name == '0') $name = 'id'; // warum auch immer...

		switch ($name) {
			case 'id':
			case 'prefix':
			case 'suffix':
			case 'ifempty':
			case 'instead':
			case 'callback':
				$args[$name] = (string) $value;
		}

		return $args;
	}

	/**
	 * Parameter aus args auf die Ausgabe eines Widgets anwenden
	 */
	protected function handleGlobalWidgetParams($varname, $args, $value) {
		return $value;
	}

	/**
	 * Parameter aus args auf den Wert einer Variablen anwenden
	 */
	protected function handleGlobalVarParams($varname, $args, $value) {
		if (isset($args['callback'])) {
			$args['subject'] = $value;
			return call_user_func($args['callback'], $args);
		}

		$prefix = '';
		$suffix = '';

		if (isset($args['prefix'])) $prefix = $args['prefix'];
		if (isset($args['suffix'])) $suffix = $args['suffix'];

		if (isset($args['instead']) && $value != '') $value = $args['instead'];
		if (isset($args['ifempty']) && $value == '') $value = $args['ifempty'];

		return $prefix.$value.$suffix;
	}

	/**
	 * Parameter aus args zur Laufzeit auf den Wert einer Variablen anwenden.
	 * Wichtig für Variablen, die Variable ausgaben haben.
	 */
	protected function handleGlobalVarParamsSerialized($varname, $args, $value) {
		$varname = str_replace('"', '\"', $varname);
		$args    = str_replace('"', '\"', serialize($args));

		return 'rex_var::handleGlobalVarParams("'.$varname.'", unserialize("'.$args.'"), '.$value.')';
	}

	/**
	* Findet die Parameter der Variable $varname innerhalb des Strings $content.
	*/
	protected function getVarParams($content, $varname) {
		$result = array();
		$match  = $this->matchVar($content, $varname);

		foreach ($match as $param_str) {
			$args   = array();
			$params = rex_split_string($param_str);

			foreach ($params as $name => $value) {
				$args = $this->handleDefaultParam($varname, $args, $name, $value);
			}

			$result[] = array($param_str, $args);
		}

		return $result;
	}

	/**
	 * Durchsucht den String $content nach Variablen mit dem Namen $varname.
	 * Gibt die Parameter der Treffer (Text der Variable zwischen den []) als Array zur�ck.
	 */
	private function matchVar($content, $varname) {
		$hasVars = preg_match_all('/'.preg_quote($varname, '/').'\[([^\]]*)\]/ms', $content, $matches); 
		return $hasVars ? $matches[1] : array();
	}

	/**
	 * @return array  array(value, args)
	 */
	protected function extractArg($name, $args, $default = null) {
		$val = $default;

		if (isset($args[$name])) {
			$val = $args[$name];
			unset($args[$name]);
		}

		return array($val, $args);
	}
}
