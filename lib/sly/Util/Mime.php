<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Mime {
	/**
	 * get MIME type for a given file
	 *
	 * @deprecated  since 0.9, use sly_Util_File::getMimetype() instead
	 *
	 * @param  string $filename  the file's name (can be a virtual file, as only the extension is relevant)
	 * @return string            the found MIME type or 'application/octet-stream' as a fallback
	 */
	public static function getType($filename) {
		return sly_Util_File::getMimetype($filename);
	}
}
