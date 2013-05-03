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
class sly_Util_Configuration {

	/**
	 *
	 * @param sly_Configuration $config    a configuration instance
	 * @param string            $filename  filename to load
	 * @param boolean           $static  do not add the data to the dynamic store
	 */
	public static function loadYamlFile(sly_Configuration $config, $filename, $static) {
		if (file_exists($filename)) {
			$data = sly_Util_YAML::load($filename);

			if (!empty($data)) {
				if ($static) {
					$config->setStatic('/', $data);
				}
				else {
					$config->set('/', $data)->store();
				}
			}
		}
	}
}
