<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Directory {
	protected $directory; ///< string

	/**
	 * @param string  $directory
	 * @param boolean $createIfNeeded
	 */
	public function __construct($directory, $createIfNeeded = false) {
		$directory = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $directory);
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);

		if (!is_dir($directory) && $createIfNeeded) {
			self::create($directory);
		}

		$this->directory = $directory;
	}

	/**
	 * @param  string $path
	 * @param  int    $perm
	 * @param  bool   $throwException
	 * @return mixed
	 */
	public static function create($path, $perm = null, $throwException = false) {
		$path = self::normalize($path);

		if (!is_dir($path)) {
			$base = dirname($path);
			$perm = $perm === null ? sly_Core::getDirPerm() : (int) $perm;

			// create base path (walking recursively from the end to SLY_BASE, basically);
			// make sure that we don't accidentally run into an endless loop, whyn trying
			// to create "/foo/bar" on Windows (dirname("/foo") === '')
			if ($base !== false && $base !== '' && !self::create($base, $perm, $throwException)) return false;

			// try to create the directory
			if (@mkdir($path)) {
				@chmod($path, $perm);
				clearstatcache();
			}
			else {
				if ($throwException) {
					throw new sly_Util_DirectoryException($path);
				}
				else {
					trigger_error('mkdir('.$path.') failed.', E_USER_WARNING);
					return false;
				}
			}
		}

		return $path;
	}

	/**
	 * @return boolean
	 */
	public function exists() {
		return is_dir($this->directory);
	}

	/**
	 * @param  boolean $files
	 * @param  boolean $directories
	 * @param  boolean $dotFiles
	 * @param  boolean $absolute
	 * @param  string  $sortFunction
	 * @return array
	 */
	public function listPlain($files = true, $directories = true, $dotFiles = false, $absolute = false, $sortFunction = 'natsort') {
		if (!$files && !$directories) return array();
		if (!is_dir($this->directory)) return false;

		if (!empty($sortFunction) && !function_exists($sortFunction)) {
			throw new sly_Exception(t('sort_function_not_found', $sortFunction));
		}

		$handle = @opendir($this->directory);
		$list   = array();

		if (!$handle) {
			return false;
		}

		while ($file = readdir($handle)) {
			if ($file == '.' || $file == '..') continue;
			if ($file[0] == '.' && !$dotFiles) continue;

			$abs = self::join($this->directory, $file);

			if (is_dir($abs)) {
				if ($directories) $list[] = $absolute ? $abs : $file;
			}
			else {
				if ($files) $list[] = $absolute ? $abs : $file;
			}
		}

		closedir($handle);

		if (!empty($sortFunction)) {
			$sortFunction($list);
			$list = array_values($list);
		}

		return $list;
	}

	/**
	 * @param  boolean $dotFiles
	 * @param  boolean $absolute
	 * @return array
	 */
	public function listRecursive($dotFiles = false, $absolute = false) {
		if (!is_dir($this->directory)) return false;
		// use the realpath of the directory to normalize the filenames
		$iterator = new RecursiveDirectoryIterator(realpath($this->directory));
		$iterator = new RecursiveIteratorIterator($iterator);
		$list     = array();
		$baselen  = strlen(rtrim(realpath($this->directory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);

		foreach ($iterator as $filename => $fileInfo) {
			if ($iterator->isDot()) continue;

			if ($dotFiles && $absolute) {
				$list[] = $filename;
				continue;
			}

			// use the fast way to find dotfiles
			if (!$dotFiles && substr_count($filename, DIRECTORY_SEPARATOR.'.') > 0) {
				continue;
			}

			$list[] = $absolute ? $filename : substr($filename, $baselen);
		}

		natcasesort($list);
		return array_values($list);
	}

	/**
	 * @param  boolean $force
	 * @return boolean
	 */
	public function delete($force = false) {
		if (!$this->exists()) return true;

		$empty = count($this->listPlain(true, true, true, false, null)) === 0;

		if (!$empty && (!$force || !$this->deleteFiles(true))) {
			return false;
		}

		$retval = rmdir($this->directory);
		clearstatcache();

		return $retval;
	}

	/**
	 * @param  boolean $recursive
	 * @return boolean
	 */
	public function deleteFiles($recursive = false) {
		if ($this->exists()) {
			$level = error_reporting(0);

			if ($recursive) {
				// don't use listRecursive() because CHILD_FIRST matters
				$iterator = new RecursiveDirectoryIterator($this->directory);
				$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

				foreach ($iterator as $file) {
					if ($file->isDir()) {
						rmdir($file->getPathname());
					}
					else {
						unlink($file->getPathname());
					}
				}
			}
			else {
				$files = $this->listPlain(true, $recursive, true, true, null);

				if ($files) {
					array_map('unlink', $files);
				}
			}

			error_reporting($level);

			if (count($this->listPlain(true, false, true, true, null)) > 0) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Copies the content of this directory to another directory.
	 *
	 * @param  string $destination
	 * @return boolean
	 */
	public function copyTo($destination) {
		if (!$this->exists()) return false;

		$destination = sly_Util_Directory::create($destination);
		if ($destination === false) return false;

		$files = $this->listPlain(true, true, false, false);

		foreach ($files as $file) {
			$src = self::join($this->directory, $file);
			$dst = self::join($destination, $file);

			if (is_dir($src)) {
				$dst = self::create($dst);
				if ($dst === false) return false;

				$dir       = new sly_Util_Directory($src);
				$recursion = $dir->copyTo($dst);

				if ($recursion === false) return false;
			}
			elseif (is_file($src)) {
				if (copy($src, $dst)) chmod($dst, sly_Core::getFilePerm());
				else return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$dir = realpath($this->directory);
		return $dir ? $dir : $this->directory.' (not existing)';
	}

	/**
	 * @param  string $paths
	 * @return string
	 */
	public static function join($paths) {
		$paths = func_get_args();
		$isAbs = $paths[0][0] == '/' || $paths[0][0] == '\\';

		foreach ($paths as $idx => &$path) {
			if ($path === null || $path === false || $path === '') {
				unset($paths[$idx]);
				continue;
			}

			$path = trim(self::normalize($path), DIRECTORY_SEPARATOR);
		}

		return ($isAbs ? DIRECTORY_SEPARATOR : '').implode(DIRECTORY_SEPARATOR, $paths);
	}

	/**
	 * @param  string $path
	 * @return string
	 */
	public static function normalize($path) {
		static $s = DIRECTORY_SEPARATOR;
		static $p = null;

		if ($p === null) {
			$p = '#'.preg_quote($s, '#').'+#';
		}

		$path  = str_replace(array('\\', '/'), $s, $path);
		$path  = rtrim($path, $s);

		if (strpos($path, $s.$s) === false) {
			return $path;
		}

		$parts = preg_split($p, $path);
		return implode($s, $parts);
	}

	/**
	 * @param  string $path
	 * @param  string $base
	 * @return string
	 */
	public static function getRelative($path, $base = null) {
		if ($base === null) $base = SLY_BASE;
		$path = self::normalize(realpath($path));
		$base = self::normalize(realpath($base));

		if (!sly_Util_String::startsWith($path, $base)) {
			return $path;
		}

		return substr($path, strlen($base) +1);
	}

	/**
	 * @param  string $path
	 * @return boolean
	 */
	public static function createHttpProtected($path, $throwException = false) {
		$status = self::create($path, null, $throwException);

		if ($status && !file_exists($path.'/.htaccess')) {
			$htaccess = "order deny,allow\ndeny from all";
			$status   = @file_put_contents($path.'/.htaccess', $htaccess) > 0;

			if ($status) {
				chmod($path.'/.htaccess', sly_Core::getFilePerm());
			}
		}

		return $status;
	}

	/**
	 * Fixes ISO-8859-1 characters in filenames
	 *
	 * On Windows systems, the filesystem API returns ISO-8859-1 strings for
	 * filenames and directories. This method will return the UTF-8 encoded name
	 * if it's called on a Windows machine. Otherwise, the $filename will be
	 * returned without any changes.
	 *
	 * Use this when you want to display filenames or want to treat filenames
	 * with string functions. Be aware that you cannot use the UTF-8 encoded
	 * filename for the filesystem API (like in file_exists()).
	 *
	 * @since  0.6.2
	 * @param  string $filename  the filename as it was returned from PHP's filesystem functions
	 * @return string
	 */
	public static function fixWindowsDisplayFilename($filename) {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? utf8_encode($filename) : $filename;
	}
}
