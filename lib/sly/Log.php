<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Simple logging class
 *
 * This class provides an easy way to log data to the disk. ALl log files will
 * be placed in data/dyn/internal/sally/logs.
 *
 * @author  christoph@webvariants.de
 * @since   0.1
 * @ingroup core
 */
class sly_Log {
	protected $filename;        ///< string   the current target file
	protected $format;          ///< string   the line format to use
	protected $maxFiles;        ///< int      how many rotated files should be kept
	protected $maxSize;         ///< int      how large a single file may become
	protected $enableRotation;  ///< boolean  whether or not to use log rotation

	private static $instances = array(); ///< array   list of instances (one per log file)
	private static $targetDir = null;    ///< string  path for new log instances

	const LEVEL_INFO    = 0; ///< int
	const LEVEL_WARNING = 1; ///< int
	const LEVEL_ERROR   = 2; ///< int

	const FORMAT_SIMPLE   = '[%date% %time%] %typename%: %message%';
	const FORMAT_EXTENDED = '[%date% %time%] %typename%: %message% (IP: %ip%, Hostname: %hostname%)';
	const FORMAT_CALLER   = '[%date% %time%] %typename%: %message% (%caller%, %called%)';

	/**
	 * Set the log directory
	 *
	 * This sets where the newly created logfiles will be placed. Pass a directory
	 * (will automatically be converted to an absolute path).
	 *
	 * @throws sly_Exception  if the directory does not exist and could not be created
	 * @param  string $dir    the directory
	 * @return string         the new directory which is being used from then on
	 */
	public static function setLogDirectory($dir) {
		if (!is_dir($dir)) {
			if (!class_exists('sly_Util_Directory') || !sly_Util_Directory::create($dir)) {
				throw new sly_Exception('Could not find log directory "'.$dir.'".');
			}
		}

		self::$targetDir = rtrim(realpath($dir), DIRECTORY_SEPARATOR);
		return self::$targetDir;
	}

	/**
	 * The directory where the logfile will be put
	 *
	 * @return string  the absolute path to the log dir
	 */
	public static function getLogDirectory() {
		// fallback in case the class is loaded via bootcache
		if (self::$targetDir === null) {
			self::setLogDirectory(SLY_DYNFOLDER.'/internal/sally/logs');
		}

		return self::$targetDir;
	}

	/**
	 * Get a logger instance
	 *
	 * This method returns the instance for a specific file. The extension ".log"
	 * will be appended automatically to the given $name.
	 *
	 * Only [a-z], [0-9], _ and - are allowed in $name.
	 *
	 * @param  string $name  the target file's basename (without extension)
	 * @return sly_Log       the logger instance
	 */
	public static function getInstance($name) {
		$name = preg_replace('#[^a-z0-9-_]#i', '_', $name);
		$dir  = self::getLogDirectory();
		$key  = $dir.'/'.$name;

		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new self($key.'.log');
		}

		return self::$instances[$key];
	}

	/**
	 * Constructor
	 *
	 * @param string  $filename  the filename to use
	 */
	private function __construct($filename) {
		$this->filename       = $filename;
		$this->format         = self::FORMAT_SIMPLE;
		$this->maxFiles       = 10;
		$this->maxSize        = 1048576;
		$this->enableRotation = false;
	}

	/**
	 * Set the log format to use
	 *
	 * You can use the following placeholders in $format:
	 *
	 *  - %date%      (%Y-%m-%d)
	 *  - %time%      (%H:%M:%S)
	 *  - %typename%  (Info, Error, Warning or Dump)
	 *  - %message%   (the user defined message)
	 *  - %ip%        (the current user's IP or "<not set>" for cmdline calls)
	 *  - %hostname%  (the current user's hostname or "N/A" if not available)
	 *  - %caller%    (the function the called the logger instance)
	 *  - %called%    (the line number where the logger was called)
	 *
	 * Everything else in $format will be left as is.
	 *
	 * @param string $format  the new log format
	 */
	public function setFormat($format) {
		$this->format = trim($format);
	}

	/**
	 * Log an information
	 *
	 * This method is just a wrapper for log() called with sly_log::LEVEL_INFO.
	 *
	 * @param  string $text     the text to log
	 * @param  int    $depth    the steps that should be skipped in the stacktrace
	 *                          when finding the caller method.
	 * @param  array  $context  additional values for custom placeholders
	 * @return boolean          true if writing to the log was successful, else false
	 */
	public function info($text, $depth = 1, array $context = array()) {
		return $this->log(self::LEVEL_INFO, $text, $depth + 1, $context);
	}

	/**
	 * Log a warning message
	 *
	 * This method is just a wrapper for log() called with
	 * sly_log::LEVEL_WARNING.
	 *
	 * @param  string $text     the text to log
	 * @param  int    $depth    the steps that should be skipped in the stacktrace
	 *                          when finding the caller method.
	 * @param  array  $context  additional values for custom placeholders
	 * @return boolean          true if writing to the log was successful, else false
	 */
	public function warning($text, $depth = 1, array $context = array()) {
		return $this->log(self::LEVEL_WARNING, $text, $depth + 1, $context);
	}

	/**
	 * Log an error message
	 *
	 * This method is just a wrapper for log() called with sly_log::LEVEL_ERROR.
	 *
	 * @param  string $text     the text to log
	 * @param  int    $depth    the steps that should be skipped in the stacktrace
	 *                          when finding the caller method.
	 * @param  array  $context  additional values for custom placeholders
	 * @return boolean          true if writing to the log was successful, else false
	 */
	public function error($text, $depth = 1, array $context = array()) {
		return $this->log(self::LEVEL_ERROR, $text, $depth + 1, $context);
	}

	/**
	 * Log a generic message
	 *
	 * This function is used to perform the actual logging. You have to specify
	 * both a log level (every level is logged, so it's just used for the type
	 * string in the log line) and the message (preferably single-line).
	 *
	 * Via $depth can be controlled, how many call steps the method should skip
	 * when finding the caller. This is useful when you don't log directly, but
	 * through a custom log wrapper function. In this case the caller would
	 * always the this wrapper and not the actual line of code that called this
	 * wrapper.
	 *
	 * @throws sly_Exception    if provided with an invalid log level
	 *
	 * @param  int    $level    one of LEVEL_INFO, LEVEL_WARNING and LEVEL_ERROR
	 * @param  string $message  the text to log
	 * @param  int    $depth    the steps that should be skipped in the
	 *                          stacktrace when finding the caller method.
	 * @param  array  $context  additional values for custom placeholders
	 * @return boolean          true if writing to the log was successful, else false
	 */
	public function log($level, $message, $depth = 1, array $context = array()) {
		if ($level != self::LEVEL_INFO && $level != self::LEVEL_ERROR && $level != self::LEVEL_WARNING) {
			throw new sly_Exception('Unknown log level '.$level.'!');
		}

		switch ($level) {
			case self::LEVEL_ERROR:
				$line = $this->replacePlaceholders($this->format, 'Error', $depth, $context);
				break;

			case self::LEVEL_WARNING:
				$line = $this->replacePlaceholders($this->format, 'Warning', $depth, $context);
				break;

			case self::LEVEL_INFO:
				$line = $this->replacePlaceholders($this->format, 'Info', $depth, $context);
				break;
		}

		$line = str_replace('%message%', $message, $line);
		$line = trim($line);

		return $this->write($line);
	}

	/**
	 * Dump an arbitrary PHP variable to the log
	 *
	 * This message is just awesome if you frequently need the values of complex
	 * structures like deeply nested arrays. You can throw any value in this
	 * method and it will do it's best to print it out nicely in the log.
	 *
	 * Beware that dumping a multi-line element (like texts with line-breaks or
	 * arrays) result in a log file that cannot be parsed by simple reading the
	 * lines one after another.
	 *
	 * @param  string $name        the name of whatever you're logging there.
	 *                             The dollar sign will be added automatically.
	 * @param  mixed  $value       an arbitrary value
	 * @param  string $displayFct  Use this if you want to override the way the
	 *                             value will be printed. Use a PHP callback
	 *                             like "print_r" or your own function.
	 * @param  int    $depth       the steps that should be skipped in the
	 *                             stacktrace when finding the caller method.
	 * @param  array  $context     additional values for custom placeholders
	 * @return boolean             true if writing to the log was successful, else false
	 */
	public function dump($name, $value, $displayFct = null, $depth = 1, array $context = array()) {
		$line = $this->replacePlaceholders($this->format, 'Dump', $depth, $context);

		if ($displayFct) {
			$value = $displayFct($value);
		}
		else {
			$value = sly_Util_String::stringify($value);
		}

		$line = str_replace('%message%', '$'.$name.' = '.$value, $line);
		$line = trim($line);

		return $this->write($line);
	}

	/**
	 * Clears the logfile
	 */
	public function clear() {
		file_exists($this->filename) && file_put_contents($this->filename, '');
	}

	/**
	 * Removes the logfile
	 *
	 * @return boolean  true if successful, else false
	 */
	public function remove() {
		return @unlink($this->filename);
	}

	/**
	 * Get the filename
	 *
	 * @return string  the absolute path to the logfile
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * @param int $maxSize   max number of files to keep
	 * @param int $maxFiles  max filesize
	 */
	public function enableRotation($maxSize = 1048576, $maxFiles = 10) {
		$this->enableRotation = true;
		$this->maxFiles       = $maxFiles < 1 ? 1 : (int) $maxFiles;
		$this->maxSize        = $maxSize < 512 ? 512 : (int) $maxSize;
	}

	public function disableRotation() {
		$this->enableRotation = false;
	}

	/**
	 * @return int  the number of files to be kept
	 */
	public function getMaxFiles() {
		return $this->maxFiles;
	}

	/**
	 * @return int  the maximum filesize of one log file
	 */
	public function getMaxSize() {
		return $this->maxSize;
	}

	/**
	 * @return boolean  whether or not rotation is enabled
	 */
	public function isRotating() {
		return $this->enableRotation;
	}

	/**
	 * @return boolean  true if written successfully, else false
	 */
	public function emptyLine() {
		return $this->write('');
	}

	/**
	 * @param  string $line  line to write to the logfile
	 * @return boolean       true if written successfully, else false
	 */
	protected function write($line) {
		if (!file_exists($this->filename)) {
			@touch($this->filename);
			@chmod($this->filename, sly_Core::getFilePerm());
		}

		if ($this->enableRotation) {
			$this->rotate();
		}

		return file_put_contents($this->filename, $line."\n", FILE_APPEND) > 0;
	}

	/**
	 * @param  string $line      the line to work on
	 * @param  string $typename  'Error', 'Info' and so on
	 * @param  int    $depth     current stack depth (for getting the caller, when required)
	 * @param  array  $context   additional values for custom placeholders
	 * @return string            the line with replaced placeholders
	 */
	protected function replacePlaceholders($line, $typename, $depth, array $context = array()) {
		$ip   = isset($context['ip']) ? $context['ip'] : (empty($_SERVER['REMOTE_ADDR']) ? '<not set>' : $_SERVER['REMOTE_ADDR']);
		$line = str_replace('%ip%', $ip, $line);
		$line = str_replace('%date%', isset($context['date']) ? $context['date'] : strftime('%Y-%m-%d'), $line);
		$line = str_replace('%time%', isset($context['time']) ? $context['time'] : strftime('%H:%M:%S'), $line);
		$line = str_replace('%typename%', $typename, $line);

		if (strpos($line, '%hostname%') !== false) {
			$host = isset($context['hostname']) ? $context['hostname'] : (empty($_SERVER['REMOTE_ADDR']) ? 'N/A' : gethostbyaddr($_SERVER['REMOTE_ADDR']));
			$line = str_replace('%hostname%', $host, $line);
		}

		if (strpos($line, '%caller%') !== false || strpos($line, '%called%') !== false) {
			$caller = $this->getCaller($depth + 1);
			$file   = $caller['line'] == 0 ? '[PHPcore]' : basename($caller['file']).':'.$caller['line'];
			$line   = str_replace('%called%', $file, $line);

			if ($caller['caller'] != '[PHPcore]') {
				$line = str_replace('%caller%', $caller['caller'].'()', $line);
			}
			else {
				$line = str_replace('%caller%', $caller['caller'], $line);
			}
		}

		// replace custom placeholders
		$exclude = array('ip', 'date', 'time', 'hostname');

		foreach ($context as $key => $value) {
			if (in_array($key, $exclude)) continue;
			$line = str_replace('%'.$key.'%', sly_Util_String::stringify($value), $line);
		}

		return $line;
	}

	/**
	 * @param  int $depth  current stack depth (for getting the caller, when required)
	 * @return array       the stack as a nicely formatted array
	 */
	protected function getCaller($depth) {
		$trace = array_slice(debug_backtrace(), $depth);

		// Wurde getCaller() nicht innerhalb einer Funktion aufgerufen?

		if (empty($trace)) {
			return array();
		}

		// OOP-Aufruf?

		if (isset($trace[0]['class'])) {
			$trace[0]['function'] = $trace[0]['class'].$trace[0]['type'].$trace[0]['function'];
		}

		if (isset($trace[1]['class'])) {
			$trace[1]['function'] = $trace[1]['class'].$trace[1]['type'].$trace[1]['function'];
		}

		// Im ersten Element steht, von wo aus die Funktion aufgerufen
		// wurde.

		$calledBy = array(
			'file'     => isset($trace[0]['file']) ? $trace[0]['file'] : '[PHPcore]',
			'line'     => isset($trace[0]['line']) ? $trace[0]['line'] : 0,
			'function' => $trace[0]['function'], // der Name der Fkt., die getCaller() aufgerufen hat
			'caller'   => '[PHPcore]',
			'byphp'    => false
		);

		// Falls wir noch ein Element im Array haben, dann wissen wir
		// sogar, wie die Funktion heißt, die die Funktion aufgerufen
		// hat.

		if (count($trace) >= 2) {
			$calledBy['caller'] = $trace[1]['function'];
		}

		// Falls die Funktion durch PHP aufgerufen wurde (array_walk o.ä.)
		// holen wir nicht den Aufruf durch array_walk (da wir das ja nicht
		// wissen können), sondern den Aufruf von array_walk.

		// Der Aufruf könnte auch von z.B. register_shutdown_function()
		// kommen. In dem Fall haben wir hingegen keine Angabe, wo
		// genau im Code register_shutdown_function aufgerufen
		// wurde.

		if ($calledBy['file'] == '[PHPcore]' && count($trace) >= 2) {
			$calledBy['file']  = $trace[1]['file'];
			$calledBy['line']  = $trace[1]['line'];
			$calledBy['byphp'] = true;
		}

		return $calledBy;
	}

	/**
	 * @author Zozi
	 */
	private function rotate() {
		$logfile = $this->filename;
		$canGZip = function_exists('gzencode');

		clearstatcache();

		if (file_exists($logfile) && filesize($logfile) > $this->maxSize) {
			if ($this->maxFiles == 0) {
				unlink($logfile);
				return;
			}

			$files = glob($logfile.'*'); // may be unsorted
			rsort($files);               // ..., log.3.gz, log.2.gz, log.1.gz, log

			foreach ($files as $filename) {
				if ($filename == $logfile) {
					$num         = $this->getFileNum(1);
					$newFilename = sprintf('%s.%s', $filename, $num);

					rename($filename, $newFilename);
					if ($canGZip) $this->compressLogfile($newFilename);
				}
				else {
					$newFilename = $this->getIteratedFilename($filename);

					if ($newFilename === null) unlink($filename);
					else rename($filename, $newFilename);
				}
			}
		}
	}

	/**
	 * @param  string $filename  the filename to iterate
	 * @return null|string       the new filename or null if the file should be deleted
	 */
	private function getIteratedFilename($filename) {
		$logfile = $this->filename;
		$canGZip = function_exists('gzencode');

		if ($canGZip) {
			$filename = str_replace('.gz', '', $filename);
		}

		$num = substr($filename, strrpos($filename, '.') + 1);

		// signal to delete this file
		if ($num == $this->maxFiles) return null;

		// create new filename
		$next = $this->getFileNum($num + 1);
		$frmt = $canGZip ? '%s.%s.gz' : '%s.%s';

		return sprintf($frmt, $logfile, $next);
	}

	/**
	 * @param  int $i  file index
	 * @return string  padded file index
	 */
	private function getFileNum($i) {
		return str_pad($i, strlen($this->maxFiles), '0', STR_PAD_LEFT);
	}

	/**
	 * @param string $filename  the file to compress
	 */
	private function compressLogfile($filename) {
		if (!function_exists('gzencode')) return;

		if ($this->maxSize > 2097152) { // 2 MB
			$in  = fopen($filename, 'rb');
			$out = gzopen($filename.'.gz', 'wb');

			while (!feof($in)) {
				gzwrite($out, fread($in, 32768)); // 32 KB
			}

			gzclose($out);
			fclose($in);
		}
		else {
			file_put_contents($filename.'.gz', gzencode(file_get_contents($filename), 9));
		}

		unlink($filename);
	}
}
