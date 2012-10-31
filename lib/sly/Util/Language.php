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
 * @ingroup util
 */
class sly_Util_Language {
	/**
	 * @param  int $languageID
	 * @return sly_Model_Language
	 */
	public static function findById($languageID) {
		return sly_Service_Factory::getLanguageService()->findById($languageID);
	}

	/**
	 * @param  boolean $keysOnly
	 * @return array
	 */
	public static function findAll($keysOnly = false) {
		return sly_Service_Factory::getLanguageService()->findAll($keysOnly);
	}

	/**
	 * @param  int $languageID
	 * @return boolean
	 */
	public static function exists($languageID) {
		$languages  = self::findAll();
		$languageID = (int) $languageID;

		return isset($languages[$languageID]);
	}

	/**
	 * @param  int $languageID
	 * @return string
	 */
	public static function getLocale($languageID = null) {
		if ($languageID === null) {
			$languageID = sly_Core::getCurrentClang();
		}
		elseif (!self::exists($languageID)) {
			throw new sly_Exception(t('language_not_found', $languageID));
		}

		$languageID = (int) $languageID;
		$language   = sly_Service_Factory::getLanguageService()->findById($languageID);

		return $language->getLocale();
	}

	/**
	 * @return boolean
	 */
	public static function isMultilingual() {
		return count(self::findAll()) > 1;
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  int            $clang
	 * @return boolean
	 */
	public static function hasPermissionOnLanguage(sly_Model_User $user, $clang) {
		return $user->isAdmin() || $user->hasRight('language', 'access', $clang);
	}
}
