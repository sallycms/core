<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_User {
	/**
	 * return currently logged-in user
	 *
	 * @return sly_Model_User
	 */
	public static function getCurrentUser() {
		return sly_Service_Factory::getUserService()->getCurrentUser();
	}

	/**
	 * @param  int $userId
	 * @return sly_Model_User
	 */
	public static function findById($userId) {
		return sly_Service_Factory::getUserService()->findById($userId);
	}

	/**
	 * checks wheter a user exists or not
	 *
	 * @param  int $userId
	 * @return boolean
	 */
	public static function exists($userId) {
		return self::isValid(self::findById($userId));
	}

	/**
	 * @param  sly_Model_User $user
	 * @return boolean
	 */
	public static function isValid($user) {
		return $user instanceof sly_Model_User;
	}
}
