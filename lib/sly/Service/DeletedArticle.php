<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_DeletedArticle extends sly_Service_ArticleBase {
	protected $tablename = 'article'; ///< string

	/**
	 * @return string
	 */
	protected function getModelType() {
		return 'article';
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Article
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
	}

	/**
	 *
	 * @param  int            $id   a article id
	 * @param  sly_Model_User $user creator or null for the current user
	 * @throws sly_Exception
	 */
	public function restore($id, sly_Model_User $user = null) {
		$user    = $this->getActor($user, 'DeletedArticle restore');
		$article = $this->findOne(array('id' => $id, 'clang' => $this->getDefaultLanguageId()));

		if ($article === null) {
			throw new sly_Exception(t('article_not_found'));
		}

		$categoryId = $article->getCategoryId();

		if ($categoryId !== 0 && !sly_Util_Category::exists($categoryId)) {
			throw new sly_Exception(t('category_not_found', $categoryId));
		}



		$service = $article->isStartArticle() ? $this->getCategoryService() : $this->getArticleService();

		$newValues = array(
			'status'  => 0,
			'deleted' => 0,
			$service->getPositionField() => ($service->getMaxPosition($article->getParentId()) + 1)
 		);

		$db = $this->getPersistence();
		$db->update($this->getTableName(), $newValues, array('id' => $id));

		$this->deleteListCache();

		$this->getDispatcher()->notify($this->getEvent('RESTORED'), null, array('id' => $id));
	}

	/**
	 *
	 * @param  int      $id
	 * @return boolean  Whether the article exists or not. Deleted equals existing and vise versa.
	 */
	public function exists($id) {
		$count = $this->getPersistence()->fetch($this->getTableName(), 'COUNT(id) as c', array('id' => $id, 'deleted' => 1));
		return ((int) $count['c'] > 0);
	}

	protected function fixWhereClause($where) {
		if (is_string($where) && !empty($where)) {
			$where = "($where) AND deleted = 1";
		}
		else if (is_array($where)) {
			$where['deleted'] = 1;
		}
		else {
			$where = array('deleted' => 1);
		}

		return $where;
	}
}