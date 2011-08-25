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
class sly_Util_Mime {
	public static function getType($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception('Cannot get mimetype of non-existing file '.$filename.'.');
		}

		// try the new, recommended way
		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type  = finfo_file($finfo, $filename);
		}

		// argh, let's see if this old one exists
		elseif (function_exists('mime_content_type')) {
			$type = mime_content_type($filename);
		}

		// fallback to a generic type
		else {
			$types = sly_Util_YAML::load(SLY_COREFOLDER.'/config/mimetypes.yml');
			$ext   = strtolower(substr(strrchr($filename, '.'), 1));
			$type  = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
		}

		return $type;
	}
}
