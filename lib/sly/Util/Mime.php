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

		/*
		Using the new, fancy finfo extension can lead to serious problems on poorly-
		configured server (or Windows boxes). The extension will either just report
		false (which is fine, we could fallback to our list) or wrongly report data
		(e.g. 'text/plain' for .css files, in which cases falling back would not work).
		So to avoid this headache, we always use the prebuilt list of mimetypes and
		all is well.

		$type = null;

		// try the new, recommended way
		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type  = finfo_file($finfo, $filename);
		}

		// argh, let's see if this old one exists
		elseif (function_exists('mime_content_type')) {
			$type = mime_content_type($filename);
		}
		*/

		// fallback to prebuilt list
		$types = sly_Util_YAML::load(SLY_COREFOLDER.'/config/mimetypes.yml');
		$ext   = strtolower(substr(strrchr($filename, '.'), 1));
		$type  = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';

		return $type;
	}
}
