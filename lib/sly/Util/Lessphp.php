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
class sly_Util_Lessphp {
	private static $currentFile = null;

	/**
	 * parse a LESS file with lessphp
	 *
	 * There is no caching. So make sure you don't call this over and over again.
	 *
	 * @uses   lessphp
	 * @param  string $lessFile  the file to process
	 * @return string            the processed css code
	 */
	public static function process($lessFile) {
		// Do not use ->compileFile() to have the $cssFile's dirname as the *first*
		// importdir instead of the last, so users can use their own 'mixin.less'.

		self::$currentFile = $lessFile;
		$result = self::getCompiler($lessFile)->compile(file_get_contents($lessFile));
		self::$currentFile = null;

		return $result;
	}

	/**
	 * parse a LESS string
	 *
	 * @param  string $lessCode
	 * @return string            the generated CSS
	 */
	public static function processString($lessCode) {
		return self::getCompiler()->compile($lessCode);
	}

	/**
	 * get a new LESS compiler instance
	 *
	 * @param  string $filename  if given, the file's directory will be used as the first import directory
	 * @return lessc             the LESS compiler
	 */
	public static function getCompiler($filename = null) {
		$less = new lessc($filename);
		$less->setFormatter('compressed');
		$less->registerFunction('asset', array(__CLASS__, 'asset'));

		// add custom mixin package to default import dir
		$dir = (array) $less->importDir;

		foreach (sly_Core::config()->get('less_import_dirs') as $includeDir) {
			$dir[] = SLY_BASE.DIRECTORY_SEPARATOR.trim($includeDir, DIRECTORY_SEPARATOR);
		}
		// always add the file's dir as the first import dir
		if ($filename) array_unshift($dir, dirname($filename));

		$less->setImportDir(array_filter($dir));

		return $less;
	}

	/**
	 *
	 * @param  string $arg     (relative) url to asset
	 * @param  string $options t=add timestamp
	 * @return string
	 */
	public static function asset($arg, $options = 't') {
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
		if (strpos($options, 't') !== false &&
				self::$currentFile && substr($url, 0, 1) != '/' && strpos($url, ':') === false) {
			$file = dirname(self::$currentFile) . '/' . $url;
			if (file_exists($file)) {
				$url = $url.(strpos($url, '?') === false ? '?' : '&').'t='.base_convert(filemtime($file), 10, 35);
			}
		}

		return sprintf('url("%s")', $url);
	}
}
