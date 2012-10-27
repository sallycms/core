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
 * @ingroup authorisation
 */
class sly_Authorisation {
	private static $provider; ///< sly_Authorisation_Provider

	/**
	 * @param sly_Authorisation_Provider $provider
	 */
	public static function setAuthorisationProvider(sly_Authorisation_Provider $provider) {
		self::$provider = $provider;
	}

	/**
	 * checks if a sly_Authorisation_Provider is already set
	 *
	 * @return boolean
	 */
	public static function hasProvider() {
		return !is_null(self::$provider);
	}

	/**
	 * @param  int              $userId
	 * @param  string           $context
	 * @param  string           $token
	 * @param  mixed            $value
	 * @param  sly_Service_User $userService
	 * @return boolean
	 */
	public static function hasPermission($userId, $context, $token, $value = true, sly_Service_User $userService = null) {
		if (!self::$provider) {
			if (!$userService) {
				$userService = sly_Core::getContainer()->getUserService();
			}

			$user = $userService->findById($userId);
			return $user && $user->isAdmin();
		}

		try {
			return self::$provider->hasPermission($userId, $context, $token, $value);
		}
		catch (Exception $e) {
			trigger_error('An error occured in authorisationprovider, for security reasons permission was denied. Error: '.$e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	public static function getConfig() {
		return sly_Core::config()->get('authorisation');
	}
}
