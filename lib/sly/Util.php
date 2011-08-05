<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util {
	/**
	 * Includes a template
	 *
	 * This method includes a template. Params can be passed via an associative
	 * array in the second parameter. The parameters will be extracted to
	 * variables.
	 *
	 * @param array $name    template name
	 * @param array $params  template variables as an associative array of parameters
	 */
	public static function includeTemplate($name, $params = array()) {
		$service = sly_Service_Factory::getTemplateService();

		if ($service instanceof sly_Service_Template) {
			$service->includeFile($name, $params);
		}
	}
}
