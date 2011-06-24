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
	 * @param  mixed $userId
	 * @param  mixed $context
	 * @param  mixed $permission
	 * @param  mixed $objectId
	 * @return boolean
	 */
	public static function hasPermission($userId, $context, $permission, $objectId = null) {
		if (!self::$provider) {
			return true;
		} else {
			try {
				return $provider->hasPermission($userId, $context, $permission, $objectId);
			} catch (Exception $e) {
				trigger_error('An error occured in authorisationprovider, for security reasons permission was denied.', E_USER_WARNING);
				return false;
			}
		}
	}

	public static function getRights() {
		$rights = sly_Core::config()->get('PERM');
		$addonService = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();

		$addons = $addonService->getAvailableAddons();
		foreach ($addons as $addon) {
			$plugins = $pluginService->getAvailablePlugins($addon);
			foreach($plugins as $plugin) {
				$tmprights = sly_makeArray($pluginService->getProperty(array($addon,$plugin), 'perm', null));
				var_dump($tmprights);
				if(!empty($tmprights)) {
					$rights = array_merge($rights, $tmprights);
				}
			}
			$tmprights = sly_makeArray($addonService->getProperty($addon, 'perm', null));
			var_dump($tmprights);
			if(!empty($tmprights)) {
				$rights = array_merge($rights, $tmprights);
			}
		}

		return $rights;
	}
}
