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
	 * @param  int $userId
	 * @param  string $context
	 * @param  mixed $value
	 * @return boolean
	 */
	public static function hasPermission($userId, $context, $value = true) {
		if (!self::$provider) {
			$user = sly_Service_Factory::getUserService()->findById($userId);
			if($user && $user->isAdmin()) return true;
			return false;
		}

		try {
			return $provider->hasPermission($userId, $token, $value);
		}
		catch (Exception $e) {
			trigger_error('An error occured in authorisationprovider, for security reasons permission was denied. Error: '.$e->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	/**
	 * @return array  list of permissions
	 */
	public static function getRights() {
		return self::getRightsHelper('perm');
	}

	/**
	 * @return array  list of permissions
	 */
	public static function getExtendedRights() {
		return self::getRightsHelper('extperm');
	}

	/**
	 * @return array  list of permissions
	 */
	public static function getExtraRights() {
		return self::getRightsHelper('extraperm');
	}

	public static function getObjectRights() {
		return self::getRightsHelper('objectperm');
	}

	/**
	 * @param  string $key  one of 'perm', 'extperm' or 'extraperm'
	 * @return array        list of permissions
	 */
	protected static function getRightsHelper($key) {
		static $cache = array();

		if (!isset($cache[$key])) {
			$rights        = sly_Core::config()->get(strtoupper($key));
			$addonService  = sly_Service_Factory::getAddOnService();
			$pluginService = sly_Service_Factory::getPluginService();

			$addons = $addonService->getAvailableAddons();

			foreach ($addons as $addon) {
				$plugins = $pluginService->getAvailablePlugins($addon);

				foreach ($plugins as $plugin) {
					$tmprights = sly_makeArray($pluginService->getProperty(array($addon, $plugin), $key, null));

					if (!empty($tmprights)) {
						$rights = array_merge($rights, $tmprights);
					}
				}

				$tmprights = sly_makeArray($addonService->getProperty($addon, $key, null));

				if (!empty($tmprights)) {
					$rights = array_merge($rights, $tmprights);
				}
			}

			$cache[$key] = $rights;
		}

		return $cache[$key];
	}
}
