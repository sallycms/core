<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Templating like class for building SQL statements.
 *
 * Examples:
 * 'name = :name AND author = :author'
 * 'id = IN(:ids)'
 * 'id IN(:subselect)'
 *
 * @ingroup database
 */
class sly_DB_PDO_Expression {
	const ParameterMarker = '?'; ///< string

	private $expressions;       ///< array
	private $values = array();  ///< array
	private $connection;        ///< PDO

	/**
	 * @param mixed $expressions
	 */
	public function __construct($expressions = null /* [, $values ... ] */) {
		$values = null;

		if (is_array($expressions)) {
			$glue = func_num_args() > 1 ? func_get_arg(1) : ' AND ';
			list($expressions, $values) = $this->build_sql_from_hash($expressions,$glue);
		}

		if ($expressions != '') {
			if (!$values) $values = array_slice(func_get_args(), 1);

			$this->values      = $values;
			$this->expressions = $expressions;
		}
	}

	/**
	 * Bind a value to the specific one based index.
	 *
	 * There must be a bind marker for each value bound or to_s() will throw an
	 * exception.
	 *
	 * @throws sly_DB_PDO_Expression_Exception
	 * @param  int   $parameter_number
	 * @param  mixed $value
	 */
	public function bind($parameter_number, $value) {
		if ($parameter_number <= 0) {
			throw new sly_DB_PDO_Expression_Exception("Invalid parameter index: $parameter_number");
		}

		$this->values[$parameter_number-1] = $value;
	}

	/**
	 * @param array $values
	 */
	public function bind_values($values) {
		$this->values = $values;
	}

	/**
	 * Returns all the values currently bound.
	 *
	 * @return array
	 */
	public function values() {
		return $this->values;
	}

	/**
	 * Returns the connection object.
	 *
	 * @return PDO
	 */
	public function get_connection() {
		return $this->connection;
	}

	/**
	 * Sets the connection object. It is highly recommended to set this so we can
	 * use the adapter's native escaping mechanism.
	 *
	 * @param PDO $connection  a PDO instance
	 */
	public function set_connection($connection) {
		$this->connection = $connection;
	}

	/**
	 * @throws sly_DB_PDO_Expression_Exception
	 * @param  boolean $substitute
	 * @param  array   $options
	 * @return string
	 */
	public function to_s($substitute = false, &$options = null) {
		if (!$options) $options = array();

		$values = array_key_exists('values', $options) ? $options['values'] : $this->values;

		$ret        = '';
		$replace    = array();
		$num_values = count($values);
		$len        = strlen($this->expressions);
		$quotes     = 0;

		for ($i = 0, $n = strlen($this->expressions), $j = 0; $i < $n; ++$i) {
			$ch = $this->expressions[$i];

			if ($ch == self::ParameterMarker) {
				if ($quotes % 2 == 0) {
					if ($j > $num_values-1) {
						throw new sly_DB_PDO_Expression_Exception("No bound parameter for index $j");
					}

					$ch = $this->substitute($values, $substitute, $i, $j++);
				}
			}
			elseif ($ch == '\'' && $i > 0 && $this->expressions[$i-1] != '\\') {
				++$quotes;
			}

			$ret .= $ch;
		}

		return $ret;
	}

	/**
	 * @param  array  $hash
	 * @param  string $glue
	 * @return array
	 */
	private function build_sql_from_hash(&$hash, $glue) {
		$sql = $g = '';

		foreach ($hash as $name => $value) {
			if (is_array($value)) $sql .= "$g`$name` IN (?)";
			else $sql .= "$g`$name` = ?";

			$g = $glue;
		}

		return array($sql, array_values($hash));
	}

	/**
	 * @param  array   $values
	 * @param  boolean $substitute
	 * @param  int     $pos
	 * @param  int     $parameter_index
	 * @return string
	 */
	private function substitute(&$values, $substitute, $pos, $parameter_index) {
		$value = $values[$parameter_index];

		if (is_array($value)) {
			if ($substitute) {
				$ret = '';

				for ($i = 0, $n = count($value); $i < $n; ++$i) {
					$ret .= ($i > 0 ? ',' : '').$this->stringify_value($value[$i]);
				}

				return $ret;
			}

			return implode(',', array_fill(0, count($value), self::ParameterMarker));
		}

		if ($substitute) {
			return $this->stringify_value($value);
		}

		return $this->expressions[$pos];
	}

	/**
	 * @param  mixed $value
	 * @return string
	 */
	private function stringify_value($value) {
		if (is_null($value)) return 'NULL';
		return is_string($value) ? $this->quote_string($value) : $value;
	}

	/**
	 * @param  string $value
	 * @return string
	 */
	private function quote_string($value) {
		if ($this->connection) {
			return $this->connection->quote($value);
		}

		return "'".str_replace("'", "''", $value)."'";
	}
}
