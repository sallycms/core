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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_Slice extends sly_Service_Model_Base_Id {
	protected $tablename  = 'slice'; ///< string
	protected $hasCascade = true;    ///< boolean

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
		$valueservice = sly_Service_Factory::getSliceValueService();
		$clone        = $this->create(array('module' => $slice->getModule()));

		foreach ($valueservice->find(array('slice_id' => $slice->getId())) as $sliceValue) {
			$sliceValue->setId(sly_Model_Base_Id::NEW_ID);
			$sliceValue->setSliceId($clone->getId());
			$valueservice->save($sliceValue);
		}

		return $clone;
	}
}
