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
class sly_Util_Template {
	/**
	 * render a given template
	 *
	 * This method includes a template identified by its name.
	 * A unlimited number of variabled can be given to the templates
	 * through an associated array like array('varname' => 'value' ...).
	 *
	 * @param string $name    the template name
	 * @param array  $params  template variables as an associative array of parameters
	 */
	public static function render($name, $params = array()) {
		try {
			sly_Service_Factory::getTemplateService()->includeFile($name, $params);
		}
		catch (sly_Service_Template_Exception $e) {
			print $e->getMessage();
		}
	}

	/**
	 * checks if a template exists
	 *
	 * @param  string $name
	 * @return boolean
	 */
	public static function exists($name) {
		return sly_Service_Factory::getTemplateService()->exists($name);
	}
}
