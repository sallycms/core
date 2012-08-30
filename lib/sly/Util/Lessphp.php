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
	 * This method processes a file with lessphp.
	 *
	 * The generated css content will not be cached so care about is
	 * by yourselves.
	 *
	 * @uses   lessphp
	 * @param  string $cssFile  the file to process
	 * @return string           the processed css code
	 */
	public static function process($cssFile) {
		// Do not use ->compileFile() to have the $cssFile's dirname as the *first*
		// importdir instead of the last, so users can use their own 'mixin.less'.
		return self::getCompiler($cssFile)->compile(file_get_contents($cssFile));
	}

	public static function processString($css) {
		return self::getCompiler()->compile($css);
	}

	public static function getCompiler($fname = null) {
		require_once SLY_VENDORFOLDER.'/leafo/lessphp/lessc.inc.php';

		$less = new lessc($fname);
		$less->setFormatter('compressed');

		// add custom mixin package to default import dir
		$dir   = (array) $less->importDir;
		$dir[] = SLY_VENDORFOLDER.'/sallycms/less-mixins/';

		// always add the file's dir as the first import dir
		if ($fname) array_unshift($dir, dirname($fname));

		$less->setImportDir(array_filter($dir));

		return $less;
	}
}
