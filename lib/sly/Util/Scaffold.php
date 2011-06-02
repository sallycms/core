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
class sly_Util_Scaffold {
	/**
	 * This method processes a file with CSScaffold.
	 *
	 * The generated css content will not be cached so care about is
	 * by yourselves.
	 *
	 * @uses   Scaffold
	 * @param  string $cssFile  the file to process
	 * @return string           the processed css code
	 */
	public static function process($cssFile) {
		static $isScaffoldInit = false;

		if (!$isScaffoldInit) {
			$scaffoldBase = sly_Util_Directory::join(SLY_INCLUDE_PATH, 'lib', 'Scaffold');
			require_once $scaffoldBase.'/libraries/Bootstrap.php';

			if (!defined('SCAFFOLD_PRODUCTION')) {
				define('SCAFFOLD_PRODUCTION', false);
			}

			$cacheDir = SLY_DYNFOLDER.'/internal/sally/css-cache';
			sly_Util_Directory::create($cacheDir, 0777);

			$config = array(
				'document_root'    => SLY_BASE,
				'system'           => $scaffoldBase,
				'cache'            => $cacheDir,
				'cache_lifetime'   => false,
				'disable_flags'    => true,
				'enable_log'       => false,
				'error_threshold'  => 1,
				'gzip_compression' => false
			);

			Scaffold::setup($config);
			$isScaffoldInit = true;
		}

		return Scaffold::process($cssFile);
	}
}
