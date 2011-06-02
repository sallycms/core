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

	public static function exists($languageID) {
		$languages  = self::findAll();
		$languageID = (int) $languageID;

		return isset($languages[$languageID]);
	}

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

	public static function isMultilingual() {
		return count(self::findAll()) > 1;
	}
}
