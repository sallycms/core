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
	protected $file;   ///< string
	protected $params; ///< array

	/**
	 * @throws sly_Exception
	 * @param  string $filename
	 */
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception('Datei nicht gefunden: '.$filename);
		}

		$this->file   = $filename;
		$this->params = null;
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key = null, $default = false) {
		$this->getParams();
		if ($key === null) return $this->params;
		return isset($this->params[$key]) ? $this->params[$key] : $default;
	}

	/**
	 * @return array
	 */
	protected function getParams() {
		if ($this->params === null) {
			$this->parseFile();
		}

		return $this->params;
	}

	/**
	 * @return boolean
	 */
	protected function parseFile() {
		$contents = file_get_contents($this->file);
		$match    = array();

		preg_match('#/\*\*(.*?)\*/#is', $contents, $match);

		if (empty($match)) {
			$this->params = array();
			return false;
		}

		$content      = trim($match[1]);
		$this->params = array();

		// add a pseudo tage to make the regex easier
		$content .= ' * @sly';

		preg_match_all('/
			\*\s*@sly             # match the beginning of a tag, like "* @sly"
			\s+(.*?)              # a bit of whitespace, followed by the tag name
			\s+(.*?)              # a bit of whitespace and the actual content
			(?=(\s*\*\s*)?@sly)   # the content ends with the next "@sly"
			                      # which can be preceeded by " * "
			/ixs', $content, $matches, PREG_SET_ORDER
		);

		foreach ($matches as $match) {
			$key   = trim($match[1]);
			$value = trim($match[2]);

			// if we got a multiline value, replace the " *     " at the beginning of each line
			$value = preg_replace('/^\s*\*\s*/m', '', $value);

			// and lastly replace the newlines with spaces
			$value = str_replace("\n", ' ', $value);

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
