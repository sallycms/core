<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Asset_Compiler_Lessphp {
	protected $lessc;
	protected $currentFile;

	public function __construct(lessc $compiler) {
		$this->lessc       = $compiler;
		$this->currentFile = null;
	}

	/**
	 * get less compiler
	 *
	 * @return lessc
	 */
	public function getLessphp() {
		return $this->lessc;
	}

	/**
	 * remove all import dirs from the compiler
	 *
	 * @return sly_Asset_Compiler_Lessphp
	 */
	public function clearImportDirs() {
		$this->lessc->setImportDir(array());

		return $this;
	}

	/**
	 * add a new import dir to the compiler
	 *
	 * @param  string  $directory
	 * @param  boolean $prepend
	 * @return sly_Asset_Compiler_Lessphp
	 */
	public function addImportDir($directory, $prepend = false) {
		if (!is_dir($directory)) {
			throw new sly_Exception('Import directory "'.directory.'" does not exist.');
		}

		$dirs = (array) $this->lessc->importDir;

		if ($prepend) {
			array_unshift($dirs, realpath($directory));
		}
		else {
			$dirs[] = realpath($directory);
		}

		$this->lessc->setImportDir(array_unique($dirs));

		return $this;
	}

	/**
	 * parse a LESS file with lessphp
	 *
	 * There is no caching. So make sure you don't call this over and over again.
	 *
	 * @uses   lessphp
	 * @param  string $lessFile  the file to process
	 * @return string            the processed css code
	 */
	public function process($lessFile) {
		if (!file_exists($lessFile)) {
			throw new sly_Exception('LESS file "'.$lessFile.'" does not exist.');
		}

		// note the current file for the asset() LESS function
		$this->currentFile = $lessFile;

		// remember the original state
		$lessc          = $this->lessc;
		$origImportDirs = (array) $lessc->importDir;

		// always add the file's dir as the first import dir
		$this->addImportDir(dirname($filename), true);

		// do the heavy work
		$result = $compiler->compile(file_get_contents($lessFile));

		// restore import dirs
		$lessc->setImportDir($origImportDirs);

		$this->currentFile = null;

		return $result;
	}

	/**
	 * parse a LESS string
	 *
	 * @param  string $lessCode
	 * @return string            the generated CSS
	 */
	public function processString($lessCode) {
		return $this->lessc->compile($lessCode);
	}

	/**
	 *
	 * @param  string $arg     (relative) url to asset
	 * @param  string $options t=add timestamp
	 * @return string
	 */
	public function lessAssetFunction($arg, $options = 't') {
		if (!is_string($options)) {
			$options = 't';
		}

		if ($arg[0] == 'list') {
			$url     = $arg[2][0][2][0];
			$options = $arg[2][1][2][0];
		}
		else {
			$url = $arg[2][0];
		}

		/*
		 *  if 't' option set add timestamp to url
		 */
		if (
			strpos($options, 't') !== false &&
			$this->currentFile &&
			substr($url, 0, 1) != '/' &&
			strpos($url, ':') === false
		) {
			$file = dirname($this->currentFile).'/'.$url;

			if (file_exists($file)) {
				$url = $url.(strpos($url, '?') === false ? '?' : '&').'t='.base_convert(filemtime($file), 10, 35);
			}
		}

		return sprintf('url("%s")', $url);
	}
}
