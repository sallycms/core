<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
		return sly_Core::getContainer()->getArticleSliceService()->findById($articleSliceID);
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
	 * @param  int    $articleId
	 * @param  int    $clang      give null for the current language
	 * @param  string $slot
	 * @return array              list of sly_Model_ArticleSlice objects
	 */
	public static function findByArticle($articleId, $clang = null, $slot = null) {
		$articleId = (int) $articleId;
		$clang     = $clang === null ? sly_Core::getCurrentClang() : (int) $clang;
		$where     = array('article_id' => $articleId, 'clang' => $clang);
		$order     = 'pos ASC';

		if ($slot !== null) {
			$where['slot'] = $slot;
			$order         = 'slot ASC, pos ASC';
		}

		return sly_Core::getContainer()->getArticleSliceService()->find($where, null, $order);
	}
}
