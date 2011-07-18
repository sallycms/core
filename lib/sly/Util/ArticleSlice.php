<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
	 * @param int $article_slice_id
	 * @return boolean 
	 */
	public static function exists($article_slice_id) {
		$article_slice_id = (int) $article_slice_id;
		$articleSlice   = self::findById($article_slice_id);
		return is_object($articleSlice) && ($articleSlice instanceof OOArticleSlice);
	}
	
	/**
	 * return the module name for a given slice
	 * 
	 * @param int $article_slice_id 
	 * @return string
	 */
	public static function getModuleNameForSlice($article_slice_id) {
		$article_slice_id = (int) $article_slice_id;
		$articleSlice   = self::findById($article_slice_id);
		if(is_null($articleSlice)) return '';
		return $articleSlice->getModule();
	}
	
	/**
	 *
	 * @param int $article_slice_id
	 * @return OOArticleSlice 
	 */
	public static function findById($article_slice_id) {
		return OOArticleSlice::getArticleSliceById($article_slice_id);
	}
	
	/**
	 * tries to delete a slice
	 * 
	 * @param int $article_slice_id
	 * @return boolean 
	 */
	public static function deleteById($article_slice_id) {
		$article_slice_id = (int) $article_slice_id;
		if(!self::exists($article_slice_id)) return false;
		
		$article_slice = self::findById($article_slice_id);
		
		//remove cachefiles
		sly_Util_Slice::clearSliceCache($article_slice->getSliceId());

		$sql = sly_DB_Persistence::getInstance();
		$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		//fix order
		$sql->query('UPDATE '.$pre.'article_slice SET prior = prior -1 WHERE '.
			sprintf('article_id = %d AND clang = %d AND slot = "%s" AND prior > %d',
			$article_slice->getArticleId(), $article_slice->getClang(), $article_slice->getSlot(), $article_slice->getPrior()
		));

		//delete articleslice
		$sql->delete('article_slice', array('id' => $article_slice_id));

		//delete slice
		sly_Service_Factory::getSliceService()->delete(array('id' => $article_slice->getSliceId()));

		// TODO delete less entries in cache
		sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		return $sql->affectedRows() == 1;
	}
	
}

