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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_Slice extends sly_Service_Model_Base_Id {
	protected $tablename  = 'slice'; ///< string

	/**
	 * @param  array $params
	 * @return sly_Model_Slice
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Slice($params);
	}

	/**
	 * Kopiert einen Slice und seine Values
	 *
	 * @param  sly_Model_Slice $slice
	 * @return sly_Model_Slice
	 */
	public function copy(sly_Model_Slice $slice) {
		$new = new sly_Model_Slice($slice->toHash());
		return $this->save($new);
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Slice $slice
	 * @return int
	 */
	public function deleteBySlice(sly_Model_Slice $slice) {
		return $this->deleteById($slice->getId());
	}
}
