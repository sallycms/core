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
	 * checks wheter an articleslice exists or not
	 *
	 * @param  int $article_slice_id
	 * @return boolean
	 */
	public static function exists($article_slice_id) {
		$articleSlice = self::findById($article_slice_id);
		return is_object($articleSlice) && ($articleSlice instanceof sly_Model_ArticleSlice);
	}

	/**
	 * return the module name for a given slice
	 *
	 * @param int $article_slice_id
	 * @return string
	 */
	public static function getModuleNameForSlice($article_slice_id) {
		$articleSlice = self::findById($article_slice_id);

		if (is_null($articleSlice)) return '';
		return $articleSlice->getSlice()->getModule();
	}

	/**
	 * @param int $article_slice_id
	 * @return sly_Model_ArticleSlice
	 */
	public static function findById($article_slice_id) {
		$article_slice_id = (int) $article_slice_id;
		return sly_Service_Factory::getArticleSliceService()->findById($article_slice_id);
	}

	/**
	 * tries to delete a slice
	 *
	 * @param int $article_slice_id
	 * @return boolean
	 */
	public static function deleteById($article_slice_id) {
		$article_slice_id = (int) $article_slice_id;
		if (!self::exists($article_slice_id)) return false;

		return sly_Service_Factory::getArticleSliceService()->deleteById($article_slice_id);
	}

	public static function getModule($article_slice_id) {
		$slice = self::findById($article_slice_id);

		if (is_null($slice)) return false;

		$module = $slice->getModule();
		return sly_Service_Factory::getModuleService()->exists($module) ? $module : false;
	}

	/**
	 * @param  int    $articleId
	 * @param  int    $clang
	 * @param  string $slot
	 * @return array
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

		return sly_Service_Factory::getArticleSliceService()->find($where, null, $order);
	}
}
