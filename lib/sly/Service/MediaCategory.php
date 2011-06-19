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
 * DB Model Klasse für Medienkategorien
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_MediaCategory extends sly_Service_Model_Base_Id {
	protected $tablename = 'file_category';

	protected function makeInstance(array $params) {
		return new sly_Model_MediaCategory($params);
	}
}
