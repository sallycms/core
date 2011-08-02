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
class sly_Util_Template {

	/**
	 * This method includes a template identified by its name.
	 * A unlimited number of variabled can be given to the templates
	 * through an associated array array('varname' => 'value' ...)
	 * 
	 * @param type $templateName
	 * @param type $params 
	 */
	public static function render($templateName, $params) {
		try {
			sly_Service_Factory::getTemplateService()->includeFile($templateName, $params);
		}
		catch(sly_Exception $e) {
			print $e->getMessage();
		}
	}
}