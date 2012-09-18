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
class sly_Util_Lessphp {
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
		return self::getCompiler($lessFile)->compile(file_get_contents($lessFile));
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
		require_once SLY_VENDORFOLDER.'/leafo/lessphp/lessc.inc.php';

		$less = new lessc($filename);
		$less->setFormatter('compressed');

		// add custom mixin package to default import dir
		$dir   = (array) $less->importDir;
		$dir[] = SLY_VENDORFOLDER.'/sallycms/less-mixins/';

		// always add the file's dir as the first import dir
		if ($filename) array_unshift($dir, dirname($filename));

		$less->setImportDir(array_filter($dir));

		return $less;
	}
}
