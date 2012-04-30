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
		sly_dump($cssFile);
		require_once SLY_SALLYFOLDER.'/vendor/leafo/lessphp/lessc.inc.php';

		$less = new lessc($cssFile);
		return $less->parse();
	}

	public static function processString($css) {
		require_once SLY_COREFOLDER.'/lib/lessphp/lessc.inc.php';
		$less = new lessc();
		return $less->parse($css);
	}
}
