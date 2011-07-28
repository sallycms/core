<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
	 * @param  sly_Model_User  $user      The user object
	 * @param  string          $password  The plain password string
	 * @return string                     The hashed password
	 * @throws sly_Exception   When createdate is empty
	 */
	public static function getPasswordHash(sly_Model_User $user, $password) {
		$createdate = $user->getCreateDate();
		if (empty($createdate)) throw new sly_Exception('Password could not be generated without a valid createdate.');
		return sly_Util_Password::hash($password, $createdate);
	}

	/**
	 * return current loggedin user
	 *
	 * @return sly_Model_User
	 */
	public static function getCurrentUser() {
		return sly_Service_Factory::getUserService()->getCurrentUser();
	}
}
