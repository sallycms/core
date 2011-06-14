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
 * Database dump
 *
 * This class wraps a single database dump and can be used to split it up and
 * import it again. Database dumps contain the Sally version and table prefix of
 * the system they were created in. These information needs to match when trying
 * to import them. They are encoded in the comments in the top of the file.
 *
 * This class is primarily used when installing Sally, but also by the
 * Import/Export addOn. It was moved here so that Sally can be installed without
 * having the addOn installed.
 *
 * @ingroup database
 * @author  Christoph
 * @since   0.3
 */
class sly_DB_Dump {
	protected $filename;  ///< string
	protected $headers;   ///< array
	protected $prefix;    ///< string
	protected $version;   ///< string
	protected $queries;   ///< array

	private $content;     ///< string

	/**
	 * Constructor
	 *
	 * Constructs the object, but does not yet do any work (like reading the
	 * file).
	 *
	 * @throws sly_Exception     if the file was not found
	 * @param  string $filename  the full path to the dump file
	 */
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception(t('file', $filename));
		}

		$this->filename = $filename;
		$this->headers  = null;
		$this->prefix   = null;
		$this->version  = null;
		$this->queries  = null;
	}

	/**
	 * Get the headers
	 *
	 * This method will read the headers from the dump file and return it.
	 * Headers are the comments at the beginning of the dump file.
	 *
	 * @return array  the found headers
	 */
	public function getHeaders() {
		return $this->getProperty('headers');
	}

	/**
	 * Get the version
	 *
	 * This method will read the version from the dump file and return it.
	 *
	 * @return string  the found version
	 */
	public function getVersion() {
		return $this->getProperty('version');
	}

	/**
	 * Get the prefix
	 *
	 * This method will read the table prefix from the dump file and return it.
	 *
	 * @return string  the found prefix
	 */
	public function getPrefix() {
		return $this->getProperty('prefix');
	}

	/**
	 * Get the queries
	 *
	 * This method will split the dump file up into individual queries and
	 * return them. When $replaceVariables is set to true, placeholders like
	 * %TABLE_PREFIX% or %USER% are replaced. Use this only when you're sure
	 * that this will not damage the dump's content.
	 *
	 * @param  boolean $replaceVariables  see description
	 * @return array                      array of queries
	 */
	public function getQueries($replaceVariables = false) {
		$queries = $this->getProperty('queries');
		if ($replaceVariables === false) return $queries;

		foreach ($queries as $idx => $qry) {
			$queries[$idx] = self::replaceVariables($qry);
		}

		return $queries;
	}

	/**
	 * Get the dump's content
	 *
	 * This method will read the dump file and return the content. Nothing more.
	 *
	 * @return string  the content
	 */
	public function getContent() {
		return file_get_contents($this->filename);
	}

	/**
	 * Get a property
	 *
	 * A property is one of the class fields. When called for the first time, it
	 * will parse the file and extract all infos. Later on, this will just return
	 * the field without doing any more work. It's just a convenience wrapper to
	 * not have to parse to file over and over again.
	 *
	 * @return mixed  the property
	 */
	protected function getProperty($name) {
		if ($this->$name === null) $this->parse();
		return $this->$name;
	}

	/**
	 * Parse the dump
	 *
	 * This method will coordinate the main work. It reads the headers, extracts
	 * version and prefix, gets the queries and so on.
	 *
	 * @return boolean  true if successful, else false
	 */
	protected function parse() {
		try {
			$this->readHeaders();

			$this->version = $this->findHeader('#^sally database dump version ([0-9.]+)#i');
			$this->prefix  = $this->findHeader('#^prefix ([a-z0-9_]+)#i');

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

	/**
	 * Read the file headers
	 *
	 * This method will read the file line by line, reading the headers. When the
	 * first non-comment is encountered, the parsing stops (so in most cases,
	 * this will read at most 2 or 3 lines).
	 *
	 * Comments can be either '--' style or '##' style.
	 */
	protected function readHeaders() {
		$f = fopen($this->filename, 'r');
		$this->headers = array();

		for (;;) {
			$line  = fgets($f, 256);
			$start = substr($line, 0, 2);

			if ($start !== '##' && $start !== '--') {
				break;
			}

			$this->headers[] = trim(substr($line, 2));
		}

		fclose($f);
	}

	/**
	 * Find a header
	 *
	 * Since headers can come in any order and have no identification, they have
	 * to be found by giving a regex and going through all of them until one is
	 * found.
	 *
	 * This method will return the contents of the first group of the given
	 * regex.
	 *
	 * @param  string  the regex to find (must contain at least one group)
	 * @return string  the first group, if found, else false
	 */
	protected function findHeader($regex) {
		foreach ($this->headers as $header) {
			if (preg_match($regex, $header, $match)) {
				return trim($match[1]);
			}
		}

		return false;
	}

	/**
	 * Replace table prefix
	 *
	 * This method will attempt to replace the table prefix for the complete
	 * file, making it compatible when importing. It does so by performing some
	 * regex magic, so it will also replace the prefix inside of the actual
	 * database contents. In many cases, this is desirable (for example, when
	 * queries are stored), but be aware that this might lead to problems with
	 * user defined (user meaning a visitor) content.
	 */
	protected function replacePrefix() {
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		if ($this->prefix && $prefix !== $this->prefix) {
			$quoted = preg_quote($this->prefix, '/');

			$this->content = preg_replace('/(TABLE `?)'.$quoted.'/i',  '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(INTO `?)'.$quoted.'/i',   '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(EXISTS `?)'.$quoted.'/i', '$1'.$prefix, $this->content);
		}
	}

	/**
	 * Replace placeholders
	 *
	 * This method will replace some special placeholders inside of a query.
	 *
	 *  - %USER% will be replaced with the currently logged in user's login
	 *  - %TIME% will be replaced with the current unix timestamp
	 *  - %TABLE_PREFIX% will be replaced with the table prefix
	 *
	 * @param  string $query  the query to work with
	 * @return string         the result
	 */
	public static function replaceVariables($query) {
		static $prefix = null;

		$user = sly_Util_User::getCurrentUser();

		if ($prefix === null) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		}

		$query = str_replace('%USER%', $user ? $user->getLogin() : '', $query);
		$query = str_replace('%TIME%', time(), $query);
		$query = str_replace('%TABLE_PREFIX%', $prefix, $query);

		return $query;
	}

	/**
	 * Split up the dump file
	 *
	 * This method will take the result of splitFile() and put the queries in
	 * the 'queries' property.
	 */
	protected function readQueries() {
		$this->splitFile();

		foreach ($this->queries as $idx => $line) {
			$this->queries[$idx] = $line['query'];
		}
	}

	/**
	 * Split up the dump file
	 *
	 * This method will perform the actual parsing of the dump. It's a copy from
	 * phpMyAdmin.
	 *
	 * @author  phpMyAdmin
	 * @license GPLv2
	 */
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
