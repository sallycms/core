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
 * @ingroup util
 */
abstract class sly_Service_File_Base {
	protected $cache;

	public function __construct() {
		$this->clearCache();
	}

	public function clearCache() {
		$this->cache = array();
	}

	/**
	 * @throws sly_Exception
	 * @return string
	 */
	abstract protected function getCacheDir();

	/**
	 * @param  string $filename
	 * @return mixed
	 */
	abstract protected function readFile($filename);

	/**
	 * @param string $filename
	 * @param mixed $data
	 */
	abstract protected function writeFile($filename, $data);

	/**
	 * @param  string $filename
	 * @return string
	 */
	public function getCacheFile($filename) {
		$dir      = $this->getCacheDir();
		$filename = realpath($filename);

		// Es kann sein, dass Dateien über Symlinks eingebunden werden. In diesem
		// Fall liegt das Verzeichnis ggf. ausßerhalb von SLY_BASE und kann dann
		// nicht so behandelt werden wie ein "lokales" AddOn.

		if (sly_Util_String::startsWith($filename, SLY_BASE)) {
			$filename = substr($filename, strlen(SLY_BASE) + 1);
		}
		else {
			// fix bad character ':' in 'C:/.../'
			$filename = str_replace(':', '', $filename);
		}

		return $dir.DIRECTORY_SEPARATOR.str_replace(DIRECTORY_SEPARATOR, '_', $filename).'.php';
	}

	/**
	 * @param  string $filename
	 * @return boolean
	 */
	public function hasChanges($filename) {
		$cacheFile = $this->getCacheFile($filename);
		return !$this->isCacheValid($filename, $cacheFile);
	}

	/**
	 * @param  string $origfile
	 * @param  string $cachefile
	 * @return boolean
	 */
	public function isCacheValid($origfile, $cachefile) {
		return file_exists($cachefile) && filemtime($origfile) < filemtime($cachefile);
	}

	/**
	 * Cached loading of a file
	 *
	 * @throws sly_Exception
	 * @param  string  $filename             path to file to load
	 * @param  boolean $forceCached          always return cached version (if it exists)
	 * @param  boolean $disableRuntimeCache  do not cache the decoded file contents in $this->cache
	 * @return mixed                         parsed content
	 */
	public function load($filename, $forceCached = false, $disableRuntimeCache = false) {
		if (mb_strlen($filename) === 0 || !is_string($filename)) {
			throw new sly_Exception('No file given!');
		}

		if (!$disableRuntimeCache) {
			$cacheKey = str_replace(array('\\', '/'), '/', $filename);

			if (isset($this->cache[$cacheKey])) {
				return $this->cache[$cacheKey];
			}
		}

		if (!is_file($filename)) {
			throw new sly_Exception(t('file_not_found', $filename));
		}

		$cachefile = $this->getCacheFile($filename);
		$config    = array();

		// get content from cache, when up to date
		if ($this->isCacheValid($filename, $cachefile) || (file_exists($cachefile) && $forceCached)) {
			// lock the cachefile
			$handle = fopen($cachefile, 'r');
			flock($handle, LOCK_SH);

			include $cachefile;

			// release lock again
			flock($handle, LOCK_UN);
			fclose($handle);
		}

		// get content from yaml file
		else {
			// lock the source
			$handle = fopen($filename, 'r');
			flock($handle, LOCK_SH);

			$config = $this->readFile($filename);

			// release lock again
			flock($handle, LOCK_UN);
			fclose($handle);

			$exists = file_exists($cachefile);

			file_put_contents($cachefile, '<?php $config = '.var_export($config, true).';', LOCK_EX);
			if (!$exists) chmod($cachefile, sly_Core::getFilePerm());
		}

		if (!$disableRuntimeCache) {
			$this->cache[$cacheKey] = $config;
		}

		return $config;
	}

	public function remove($filename) {
		$cacheFile   = $this->getCacheFile($filename);
		$exists      = file_exists($filename);
		$cacheExists = file_exists($cacheFile);
		if ($exists) {
			unlink($filename);
		}
		if ($cacheExists) {
			unlink($cacheFile);
		}
		$this->clearCache();
	}

	/**
	 * @param string $filename
	 * @param mixed  $data
	 */
	public function dump($filename, $data) {
		$exists = file_exists($filename);
		$this->writeFile($filename, $data);
		if (!$exists) chmod($filename, sly_Core::getFilePerm());
	}
}
