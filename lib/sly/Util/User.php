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
	 * Generates a password hash for a given user and a given password sting.
	 *
	 * The given user must have set at least the createdate
	 *
	 * @throws sly_Exception             When createdate is empty
	 * @param  sly_Model_User $user      The user object
	 * @param  string         $password  The plain password string
	 * @return string                    The hashed password
	 */
	public static function getPasswordHash(sly_Model_User $user, $password) {
		$createdate = $user->getCreateDate();
		if (empty($createdate)) throw new sly_Exception(t('password_needs_valid_createdate'));
		return sly_Util_Password::hash($password, $createdate);
	}

	/**
	 * return current user object
	 *
	 * @param  boolean $forceRefresh
	 * @return sly_Model_User
	 */
	public function getCurrentUser($forceRefresh = false) {
		return sly_Service_Factory::getUserService()->getCurrentUser($forceRefresh);
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
