<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_ArticleSlice {
	/**
	 * checks whether an article slice exists or not
	 *
	 * @param  int $articleSliceID
	 * @return boolean
	 */
	public static function exists($articleSliceID) {
		$articleSlice = self::findById($articleSliceID);
		return is_object($articleSlice) && ($articleSlice instanceof sly_Model_ArticleSlice);
	}

	/**
	 * return the module name for a given slice
	 *
	 * @param  int $articleSliceID
	 * @return string
	 */
	public static function getModuleNameForSlice($articleSliceID) {
		$articleSlice = self::findById($articleSliceID);
		return $articleSlice ? $articleSlice->getSlice()->getModule() : '';
	}

	/**
	 * find an article slice by its ID
	 *
	 * @param  int $articleSliceID
	 * @return sly_Model_ArticleSlice
	 */
	public static function findById($articleSliceID) {
		$articleSliceID = (int) $articleSliceID;
		return sly_Core::getContainer()->getArticleSliceService()->findOne(array('id' => $articleSliceID));
	}

	/**
	 * tries to delete a slice
	 *
	 * @param  int $articleSliceID
	 * @return boolean
	 */
	public static function deleteById($articleSliceID) {
		$articleSliceID = (int) $articleSliceID;
		if (!self::exists($articleSliceID)) return false;

		return sly_Core::getContainer()->getArticleSliceService()->deleteById($articleSliceID);
	}

	/**
	 * get module used by an article slice
	 *
	 * @param  int $articleSliceID
	 * @return mixed                the module name (string) or false if the module was not found
	 */
	public static function getModule($articleSliceID) {
		$slice = self::findById($articleSliceID);
		if (!$slice) return false;

		$module = $slice->getModule();
		return sly_Core::getContainer()->getModuleService()->exists($module) ? $module : false;
	}

	/**
	 * find all slices within an article
	 *
	 * @param  sly_Model_Article  $article   an article
	 * @param  string             $slot
	 * @return array              list of sly_Model_ArticleSlice objects
	 */
	public static function findByArticle(sly_Model_Article $article, $slot = null) {
		$where = array('article_id' => $article->getId(), 'clang' => $article->getClang(), 'revision' => $article->getRevision());
		$order = 'pos ASC';

		if ($slot !== null) {
			$where['slot'] = $slot;
			$order         = 'slot ASC, pos ASC';
		}

		return sly_Core::getContainer()->getArticleSliceService()->find($where, null, $order);
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  mixed          $slice  sly_Model_ArticleSlice or slice ID
	 * @return boolean
	 */
	public static function canEditSlice(sly_Model_User $user, $slice) {
		return self::hasSlicePermission($user, $slice, 'edit');
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  mixed          $slice  sly_Model_ArticleSlice or slice ID
	 * @return boolean
	 */
	public static function canMoveSlice(sly_Model_User $user, $slice) {
		return self::hasSlicePermission($user, $slice, 'move');
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  string         $module  module name
	 * @return boolean
	 */
	public static function canAddModule(sly_Model_User $user, $module) {
		return self::hasModulePermission($user, $module, 'add');
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  string         $module  module name
	 * @return boolean
	 */
	public static function canEditModule(sly_Model_User $user, $module) {
		return self::hasModulePermission($user, $module, 'edit');
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  string         $module  module name
	 * @return boolean
	 */
	public static function canDeleteModule(sly_Model_User $user, $module) {
		return self::hasModulePermission($user, $module, 'delete');
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  mixed          $slice       sly_Model_ArticleSlice or slice ID
	 * @param  string         $permission
	 * @return boolean
	 */
	private static function hasSlicePermission(sly_Model_User $user, $slice, $permission) {
		if (!($slice instanceof sly_Model_ArticleSlice)) {
			$sliceObj = self::findById($slice);

			if (!$sliceObj) {
				throw new sly_Exception(t('slice_not_found', $slice));
			}

			$slice = $sliceObj;
		}

		return self::hasModulePermission($user, $slice->getModule(), $permission);
	}

	/**
	 * @param  sly_Model_User $user
	 * @param  string         $module      module name
	 * @param  string         $permission
	 * @return boolean
	 */
	private static function hasModulePermission(sly_Model_User $user, $module, $permission) {
		return
			$user->isAdmin() ||
			$user->hasRight('module', $permission, sly_Authorisation_ModuleListProvider::ALL) ||
			$user->hasRight('module', $permission, $module)
		;
	}
}
