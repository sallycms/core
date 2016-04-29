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
 * @ingroup authorisation
 */
interface sly_Authorisation_Provider {

	/**
	 * @param  int    $userId
	 * @param  sring  $context
	 * @param  string $token
	 * @param  int    $objectId
	 * @return boolean
	 */
	public function hasPermission($userId, $context, $token, $value = true);
}
