<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class sly_Filesystem_LocalMedia extends sly_Filesystem_Local {

	public function __construct() {
		parent::__construct(SLY_MEDIAFOLDER);
	}

	public function getUrl($fileName) {
		return 'data/mediapool'.$fileName;
	}
}
