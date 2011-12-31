<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_Slice {
	/**
	 * clears the filecache for a slice
	 *
	 * @param int $slice_id
	 */
	public static function clearSliceCache($slice_id) {
		$cachedir = SLY_DYNFOLDER.'/internal/sally/article_slice/';
		sly_Util_Directory::create($cachedir);
		$cachefiles = glob($cachedir.$slice_id.'-*.slice.php');

		if (is_array($cachefiles)) {
			@array_map('unlink', $cachefiles);
		}
	}
}

