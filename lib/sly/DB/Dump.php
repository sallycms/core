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
 * @ingroup database
 */
class sly_DB_Dump {
	protected $filename;
	protected $headers;
	protected $prefix;
	protected $version;
	protected $charset;
	protected $queries;

	private $content;

	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception(t('file', $filename));
		}

		$this->filename = $filename;
		$this->headers  = null;
		$this->prefix   = null;
		$this->version  = null;
		$this->charset  = null;
		$this->queries  = null;
	}

	public function getHeaders() { return $this->getProperty('headers'); }
	public function getVersion() { return $this->getProperty('version'); }
	public function getCharset() { return $this->getProperty('charset'); }
	public function getPrefix()  { return $this->getProperty('prefix');  }

	public function getQueries($replaceVariables = false) {
		$queries = $this->getProperty('queries');
		if ($replaceVariables === false) return $queries;

		foreach ($queries as $idx => $qry) {
			$queries[$idx] = self::replaceVariables($qry);
		}

		return $queries;
	}

	public function getContent() {
		return file_get_contents($this->filename);
	}

	protected function getProperty($name) {
		if ($this->$name === null) $this->parse();
		return $this->$name;
	}

	protected function parse() {
		try {
			$this->readHeaders();

			$this->version = $this->findHeader('#^sally database dump version ([0-9.]+)#i');
			$this->prefix  = $this->findHeader('#^prefix ([a-z0-9_]+)#i');
			$this->charset = $this->findHeader('#^charset ([a-z0-9_-]+)#i');

			$this->content = $this->getContent();
			$this->replacePrefix();
			$this->readQueries();
			$this->content = '';

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	protected function readHeaders() {
		$f = fopen($this->filename, 'r');
		$this->headers = array();

		for (;;) {
			$line = fgets($f, 256);

			if (substr($line, 0, 2) != '##' && substr($line, 0, 2) != '--') {
				break;
			}

			$this->headers[] = trim(substr($line, 2));
		}

		fclose($f);
	}

	protected function findHeader($regex) {
		foreach ($this->headers as $header) {
			if (preg_match($regex, $header, $match)) {
				return trim($match[1]);
			}
		}

		return false;
	}

	protected function replacePrefix() {
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		if ($this->prefix && $prefix != $this->prefix) {
			// Hier case-insensitive ersetzen, damit alle möglich Schreibweisen (TABLE TablE, tAblE,..) ersetzt werden
			// Dies ist wichtig, da auch SQL innerhalb von Ein/Ausgabe der Module vom rex-admin verwendet werden
			$this->content = preg_replace('/(TABLE `?)'.preg_quote($this->prefix, '/').'/i',  '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(INTO `?)'.preg_quote($this->prefix, '/').'/i',   '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(EXISTS `?)'.preg_quote($this->prefix, '/').'/i', '$1'.$prefix, $this->content);
		}
	}

	public static function replaceVariables($query) {
		global $REX;

		static $prefix    = null;
		static $tmpPrefix = null;

		// $REX['USER'] gibts im Setup nicht.

		if (isset($REX['USER'])) {
			$query = str_replace('%USER%', $REX['USER']->getLogin(), $query);
		}

		if ($prefix === null) {
			$prefix    = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
			$tmpPrefix = sly_Core::config()->get('TEMP_PREFIX');
		}

		$query = str_replace('%TIME%', time(), $query);
		$query = str_replace('%TABLE_PREFIX%', $prefix, $query);
		$query = str_replace('%TEMP_PREFIX%', $tmpPrefix, $query);

		return $query;
	}

	protected function readQueries() {
		$this->splitFile();

		foreach ($this->queries as $idx => $line) {
			$this->queries[$idx] = $line['query'];
		}
	}

	protected function splitFile() {
		// do not trim, see bug #1030644
		//$sql          = trim($this->content);
		$sql          = rtrim($this->content, "\n\r");
		$sql_len      = strlen($this->content);
		$char         = '';
		$string_start = '';
		$in_string    = false;
		$nothing      = true;
		$time0        = time();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];

			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$this->queries[] = $sql;
						return true;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					elseif ($string_start == '`' || $sql[$i -1] != '\\') {
						$string_start = '';
						$in_string    = false;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j = 2;
						$escaped_backslash = false;
						while ($i - $j > 0 && $sql[$i - $j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j ++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start = '';
							$in_string    = false;
							break;
						}
						// ... else loop
						else {
							$i ++;
						}
					} // end if...elseif...else
				} // end for
			} // end if (in string)

			// lets skip comments (/*, -- and #)
			elseif (($char == '-' && $sql_len > $i+2 && $sql[$i+1] == '-' && $sql[$i+2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i+1 && $sql[$i+1] == '*')) {
				$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
				// didn't we hit end of string?
				if ($i === false) break;
				if ($char == '/') $i ++;
			}

			// We are not in a string, first check for delimiter...
			elseif ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$this->queries[] = array('query' => substr($sql, 0, $i), 'empty' => $nothing);

				$nothing = true;
				$sql     = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len = strlen($sql);

				if ($sql_len) {
					$i = -1;
				}
				else {
					// The submited statement(s) end(s) here
					return true;
				}
			} // end else if (is delimiter)

			// ... then check for start of a string,...
			elseif ($char == '"' || $char == '\'' || $char == '`') {
				$in_string    = true;
				$nothing      = false;
				$string_start = $char;
			} // end else if (is start of string)

			elseif ($nothing) {
				$nothing = false;
			}

			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1 = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
				header('X-pmaPing: Pong');
			} // end if
		} // end for

		// add any rest to the returned array
		if (!empty ($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$this->queries[] = array('query' => $sql, 'empty' => $nothing);
		}

		return true;
	}
}
