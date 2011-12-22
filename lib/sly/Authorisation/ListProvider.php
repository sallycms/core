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
interface sly_Authorisation_ListProvider {
	const ALL = 0;

	/**
	 * returns all ids for objects
	 *
	 * return array
	 */
	public function getObjectIds();

	/**
	 * get the title of an object
	 *
	 * @param $id an object id
	 * @return string
	 */
	public function getObjectTitle($id);

}
