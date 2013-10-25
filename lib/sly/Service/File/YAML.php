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
 * @ingroup util
 */
class sly_Service_File_YAML extends sly_Service_File_Base {
	/**
	 * @throws sly_Exception
	 * @return string
	 */
	protected function getCacheDir() {
		$dir = SLY_DYNFOLDER.'/internal/sally/yaml-cache';
		return sly_Util_Directory::create($dir, null, true);
	}

	/**
	 * @param  string $filename
	 * @return mixed
	 */
	protected function readFile($filename) {
		$this->checkForSfYaml();
		return sfYaml::load($filename);
	}

	/**
	 * @param  string $filename
	 * @param  mixed  $data
	 * @return int               number of written bytes
	 */
	protected function writeFile($filename, $data) {
		$this->checkForSfYaml();

		// be careful with the current locale, as it can affect how floats are
		// dumped (3.41 => German => 3,41 => is being parsed as junk once the file
		// is loaded again); see https://github.com/symfony/Yaml/blob/master/Inline.php
		// for the source of this workaround

		$locale = setlocale(LC_NUMERIC, 0);

		if (false !== $locale) {
			setlocale(LC_NUMERIC, 'C');
		}

		$yaml = sfYaml::dump($data, 5);

		if (false !== $locale) {
			setlocale(LC_NUMERIC, $locale);
		}

		return file_put_contents($filename, $yaml, LOCK_EX);
	}

	protected function checkForSfYaml() {
		if (!class_exists('sfYaml')) {
			throw new sly_Exception('sfYaml was not found. Did you forget `composer install`?');
		}
	}
}
