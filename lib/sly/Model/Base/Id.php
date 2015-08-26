<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Basisklasse für alle Models, die auf IDs basieren
 *
 * @author zozi@webvariants.de
 * @ingroup model
 */
abstract class sly_Model_Base_Id extends sly_Model_Base {
	const NEW_ID = -1; ///< int

	protected $_pk = array('id' => 'int'); ///< array
	protected $id  = self::NEW_ID;         ///< int

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = (int) $id;
	}
}
