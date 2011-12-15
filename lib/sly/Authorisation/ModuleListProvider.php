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
 * @ingroup authorisation
 */
class sly_Authorisation_ModuleListProvider implements sly_Authorisation_ListProvider {

	public function getObjectIds() {
		return array_keys(sly_Service_Factory::getModuleService()->getModules());
	}

	public function getObjectTitle($id) {
		return sly_Service_Factory::getModuleService()->getTitle($id);
	}

}

?>
