<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_ParamParser {
	protected $file;
	protected $params;

	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception('Datei nicht gefunden: '.$filename);
		}

		$this->file   = $filename;
		$this->params = null;
	}

	public function get($key = null, $default = false) {
		$this->getParams();
		if ($key === null) return $this->params;
		return isset($this->params[$key]) ? $this->params[$key] : $default;
	}

	protected function getParams() {
		if ($this->params === null) {
			$this->parseFile();
		}

		return $this->params;
	}

	protected function parseFile() {
		$contents = file_get_contents($this->file);
		$match    = array();

		preg_match('#/\*\*(.*?)\*/#is', $contents, $match);

		if (empty($match)) {
			$this->params = array();
			return false;
		}

		$content      = $match[1];
		$this->params = array();

		preg_match_all('#^\s*\*\s*@sly +(.*?) +(.*?)\s*$#im', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$key   = trim($match[1]);
			$value = trim($match[2]);

			try {
				$value = sfYamlInline::load($value);
			}
			catch (InvalidArgumentException $e) {
				trigger_error('Ungültiger Parameter entdeckt: "'.$value.'"', E_USER_WARNING);
			}

			$this->params[$key] = $value;
		}

		return true;
	}
}
