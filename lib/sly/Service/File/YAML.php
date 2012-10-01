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
		return sfYaml::load($filename);
	}

	/**
	 * @param  string $filename
	 * @param  mixed  $data
	 * @return int               number of written bytes
	 */
	protected function writeFile($filename, $data) {
		return file_put_contents($filename, sfYaml::dump($data, 5), LOCK_EX);
	}
}
