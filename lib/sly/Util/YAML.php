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
class sly_Util_YAML {
	protected static function getCacheDir() {
		$dir = SLY_DYNFOLDER.'/internal/sally/yaml-cache';

		if (!sly_Util_Directory::create($dir)) {
			throw new sly_Exception('Cache-Verzeichnis '.$dir.' konnte nicht erzeugt werden.');
		}

		return $dir;
	}

	public static function getCacheFile($filename) {
		$dir      = self::getCacheDir();
		$filename = realpath($filename);

		// Es kann sein, dass Dateien über Symlinks eingebunden werden. In diesem
		// Fall liegt das Verzeichnis ggf. ausßerhalb von SLY_BASE und kann dann
		// nicht so behandelt werden wie ein "lokales" AddOn.

		if (sly_Util_String::startsWith($filename, SLY_BASE)) {
			$filename = substr($filename, strlen(SLY_BASE) + 1);
		}
		else {
			// Laufwerk:/.../ korrigieren
			$filename = str_replace(':', '', $filename);
		}

		return $dir.DIRECTORY_SEPARATOR.str_replace(DIRECTORY_SEPARATOR, '_', $filename).'.php';
	}

	public static function isCacheValid($origfile, $cachefile) {
		return file_exists($cachefile) && filemtime($origfile) < filemtime($cachefile);
	}

	/**
	 * Cached loading of a YAML file
	 *
	 * @param  string $filename      Path to YAML file
	 * @param  boolean $forceCached  always return cached version (if it exists)
	 * @return mixed                 parsed content
	 * @throws sly_Exception
	 * @throws InvalidArgumentException
	 */
	public static function load($filename, $forceCached = false) {
		if (empty($filename) || !is_string($filename)) throw new sly_Exception('Keine Datei angegeben.');
		if (!file_exists($filename)) throw new sly_Exception('Datei '.$filename.' konnte nicht gefunden werden.');

		$cachefile = self::getCacheFile($filename);
		$config    = array();

		// get content from cache, when up to date
		if (self::isCacheValid($filename, $cachefile) || (file_exists($cachefile) && $forceCached)) {
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
			//lock the source
			$handle = fopen($filename, 'r');
			flock($handle, LOCK_SH);
			$config = sfYaml::load($filename);
			// release lock again
			flock($handle, LOCK_UN);
			fclose($handle);

			$exists = file_exists($cachefile);

			file_put_contents($cachefile, '<?php $config = '.var_export($config, true).';', LOCK_EX);
			if (!$exists) chmod($cachefile, sly_Core::getFilePerm());
		}

		return $config;
	}

	public static function dump($filename, $data) {
		$data   = sfYaml::dump($data, 5);
		$exists = file_exists($filename);

		file_put_contents($filename, $data, LOCK_EX);
		if (!$exists) chmod($filename, sly_Core::getFilePerm());
	}
}
