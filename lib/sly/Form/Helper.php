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
 * Helper class
 *
 * This class encapsulates some commonly used methods.
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Helper {
	private static $select;       ///< sly_Form_Select_DropDown
	private static $user;         ///< sly_Model_User
	private static $type;         ///< string
	private static $hideOffline;  ///< boolean
	private static $clang;        ///< int
	private static $i18nLoaded = false;

	/**
	 * Creates a select element with all visible media categories
	 *
	 * @param  string         $name      the elements name
	 * @param  int            $root      the root category to use
	 * @param  sly_Model_User $user      the user (null for the current one)
	 * @param  string         $id        the elements ID
	 * @return sly_Form_Select_DropDown  the generated select element
	 */
	public static function getMediaCategorySelect($name, $root = null, sly_Model_User $user = null, $id = null) {
		if (!self::$i18nLoaded) {
			sly_Core::getI18N()->appendFile(SLY_INCLUDE_PATH.'/lang/pages/mediapool/');
			self::$i18nLoaded = true;
		}

		$init   = array(0 => t('pool_kats_no'));
		$select = new sly_Form_Select_DropDown($name, '', -1, $init, $id);

		if ($root === null) {
			$rootCats = OOMediaCategory::getRootCategories();
		}
		else {
			$service  = sly_Service_Factory::getService('Media_Category');
			$rootCat  = $service->findById((int) $root);
			$rootCats = $rootCat ? array($rootCat) : null;
		}

		self::$select      = $select;
		self::$user        = $user;
		self::$type        = 'media';
		self::$hideOffline = false; // media cats cannot be offline
		self::$clang       = false; // media cats are monolingual

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				self::walkTree($rootCat, 0);
			}
		}

		return $select;
	}

	/**
	 * Creates a select element with all visible categories
	 *
	 * @param  string         $name         the elements name
	 * @param  boolean        $hideOffline  true to hide offline categories
	 * @param  int            $clang        the clang to use
	 * @param  int            $root         the root category to use
	 * @param  sly_Model_User $user         the user (null for the current one)
	 * @param  string         $id           the elements ID
	 * @return sly_Form_Select_DropDown     the generated select element
	 */
	public static function getCategorySelect($name, $hideOffline = true, $clang = null, $root = null, sly_Model_User $user = null, $id = null) {
		$select = new sly_Form_Select_DropDown($name, '', -1, array(0 => 'Homepage'), $id);

		if ($root === null) {
			$rootCats = sly_Util_Category::getRootCategories($hideOffline, $clang);
		}
		else {
			$rootCat  = sly_Util_Category::findById((int) $root, $clang);
			$rootCats = $rootCat ? array($rootCat) : null;
		}

		self::$select      = $select;
		self::$user        = $user;
		self::$type        = 'structure';
		self::$hideOffline = (boolean) $hideOffline;
		self::$clang       = $clang;

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				self::walkTree($rootCat, 0);
			}
		}

		return $select;
	}

	/**
	 * Helper function
	 *
	 * This method implements the tree walking algorithm used for both selects.
	 * It pays attention to the advancedMode[] and csw[] permissions.
	 *
	 * @param mixed $category  the current category (media or structure)
	 * @param int   $depth     current depth (to indent <option> elements)
	 */
	private static function walkTree($category, $depth) {
		if (empty($category)) return;

		if (self::canSeeCategory($category)) {
			$name = $category->getName();
			$user = sly_Util_User::getCurrentUser();

			// Die Anzeige hängt immer vom aktuellen Benutzer ab.

			if ($user->hasPerm('advancedMode[]')) {
				$name .= ' ['.$category->getId().']';
			}

			self::$select->addValue($category->getId(), str_repeat(' ', $depth*2).$name);
		}

		$children = $category->getChildren(self::$hideOffline, self::$clang);

		if (is_array($children)) {
			foreach ($children as $child) {
				self::walkTree($child, $depth + 1);
			}
		}
	}

	/**
	 * Check admin permission
	 *
	 * This method checks whether the user is admin[] or has the appropriate root
	 * category permission (media[0] or cws[o]).
	 *
	 * @return boolean  true or false
	 */
	private static function isAdmin() {
		if (!self::$user) return false;

		$isAdmin = self::$user->hasPerm('admin[]');

		switch (self::$type) {
			case 'media':     $isAdmin |= self::$user->hasPerm('media[0]'); break;
			case 'structure': $isAdmin |= self::$user->hasPerm('csw[0]'); break;
		}

		return $isAdmin;
	}

	/**
	 * Checks category visibility
	 *
	 * This method checks whether the user can view a given category.
	 *
	 * @param  mixed $category  the current category (media or structure)
	 * @return boolean          true or false
	 */
	private static function canSeeCategory($category) {
		if (self::$user === null || self::isAdmin()) return true;

		switch (self::$type) {
			case 'media':
				return self::$user->hasPerm('media['.$category->getId().']');

			case 'structure':
				return self::$user->hasCategoryRight($category->getId());

			default:
				return true;
		}
	}

	/**
	 * Parse form values
	 *
	 * This method is useful for parsing multilingual form elements. Multilingual
	 * elements consist of (N+1) elements plus a special checkbox for "use the
	 * same for all languages". This method checks this checkbox and returns the
	 * same value or the value of each form element.
	 *
	 * For monolingual elements, the value of the first element is returned. This
	 * is the default. For multilingual elements, you always get an array with
	 * the values for each language (even if the checkbox is checked and
	 * therefore the value is the same for all languages). This makes it easier
	 * to code against the form, knowing that multilingual elements *always*
	 * return an array.
	 *
	 * @param  string  $name          the element name
	 * @param  string  $default       default value if not present in POST
	 * @param  boolean $multilingual  toggles the multilingual parsing algorithm
	 * @param  string  $nameSuffix    a string that will be appened to the generated element names when working
	 *                                in multilingual mode; use this only if you know what you're doing (mainly
	 *                                (used for complex elements that append strings to the element name, like
	 *                                the old datepicker or the varisale money input which consists of an input
	 *                                and a select field)
	 * @return mixed                  the value as described above
	 */
	public static function parseFormValue($name, $default = null, $multilingual = false, $nameSuffix = '') {
		$monoName  = $name.$nameSuffix;
		$monoValue = isset($_POST[$monoName]) ? $_POST[$monoName] : $default;

		if (!$multilingual) {
			return $monoValue;
		}

		$equal  = !sly_Util_Language::isMultilingual() || sly_post('equal__'.$name, 'boolean', false);
		$values = array();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$key              = $name.'__clang_'.$clangID.$nameSuffix;
			$values[$clangID] = $equal ? $monoValue : (isset($_POST[$key]) ? $_POST[$key] : $default);
		}

		return $values;
	}
}
