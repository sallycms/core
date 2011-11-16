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
 * @ingroup util
 */
class sly_Util_Language {

	/**
	 * @param  int $articleId
	 * @param  int $clang
	 * @return sly_Model_Language
	 */
	public static function findById($languageID) {
		$languages  = self::findAll();
		$languageID = (int) $languageID;

		if (isset($languages[$languageID])) {
			return $languages[$languageID];
		}

		return null;
	}

	/**
	 * @param  boolean $keysOnly
	 * @return array
	 */
	public static function findAll($keysOnly = false) {
		$cache     = sly_Core::cache();
		$languages = $cache->get('sly.language', 'all', null);

		if ($languages === null) {
			$list      = sly_Service_Factory::getLanguageService()->find(null, null, 'id');
			$languages = array();

			foreach ($list as $language) {
				$languages[$language->getId()] = $language;
			}

			$cache->set('sly.language', 'all', $languages);
		}

		return $keysOnly ? array_keys($languages) : $languages;
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
			throw new sly_Exception('Unknown language given.');
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
	 * @param  int            $clangID
	 * @return boolean
	 */
	public static function hasPermissionOnLanguage(sly_Model_User $user, $clangID) {
		return $user->isAdmin() || $user->hasRight('clang[all]') || $user->hasRight('clang['.$clangID.']');
	}
}
