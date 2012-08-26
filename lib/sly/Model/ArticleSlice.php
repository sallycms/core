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
 * Business Model for ArticleSlices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
class sly_Model_ArticleSlice extends sly_Model_Base_Id implements sly_Model_ISlice {
	protected $article_id;
	protected $clang;
	protected $slot;
	protected $slice_id;
	protected $pos;
	protected $createdate;
	protected $updatedate;
	protected $createuser;
	protected $updateuser;
	protected $revision;
	protected $slice;   ///< sly_Model_Slice
	protected $article; ///< sly_Model_Article

	protected $_attributes = array(
		'updateuser' => 'string',
		'createuser' => 'string',
		'createdate' => 'datetime',
		'updatedate' => 'datetime',
		'pos'        => 'int',
		'article_id' => 'int',
		'clang'      => 'int',
		'slot'       => 'string',
		'slice_id'   => 'int',
		'revision'   => 'int'
	); ///< array

	/**
	 *
	 * @return int
	 */
	public function getArticleId() {
		return $this->article_id;
	}

	/**
	 *
	 * @return int
	 */
	public function getClang() {
		return $this->clang;
	}

	/**
	 *
	 * @return string
	 */
	public function getSlot() {
		return $this->slot;
	}

	/**
	 *
	 * @return int
	 */
	public function getPosition() {
		return $this->pos;
	}

	/**
	 * @deprecated  since 0.6
	 */
	public function getPrior() {
		return $this->pos;
	}

	/**
	 *
	 * @return int
	 */
	public function getSliceId() {
		return $this->slice_id;
	}

	/**
	 *
	 * @return int
	 */
	public function getCreateDate() {
		return $this->createdate;
	}

	/**
	 *
	 * @return int
	 */
	public function getUpdateDate() {
		return $this->updatedate;
	}

	/**
	 *
	 * @return string
	 */
	public function getCreateUser() {
		return $this->createuser;
	}

	/**
	 *
	 * @return int
	 */
	public function getUpdateUser() {
		return $this->updateuser;
	}

	/**
	 *
	 * @return int
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 *
	 * @return sly_Model_Article
	 */
	public function getArticle() {
		if (empty($this->article)) {
			$this->article =  sly_Util_Article::findById($this->getArticleId(), $this->getClang());
		}
		return $this->article;
	}

	/**
	 *
	 * @return sly_Model_Slice
	 */
	public function getSlice() {
		if (empty($this->slice)) {
			$this->slice = sly_Service_Factory::getSliceService()->findById($this->getSliceId());
		}
		return $this->slice;
	}

	/**
	 *
	 * @return sly_Model_ArticleSlice
	 */
	public function getPrevious() {
		$service = sly_Service_Factory::getArticleSliceService();
		$db      = sly_DB_Persistence::getInstance();

		return reset($service->find(sprintf('slot = %s AND pos < %d AND article_id = %d AND clang = %d', $db->quote($this->getSlot()), $this->getPosition(), $this->getArticleId(), $this->getClang())));
	}

	/**
	 *
	 * @return sly_Model_ArticleSlice
	 */
	public function getNext() {
		$service = sly_Service_Factory::getArticleSliceService();
		$db      = sly_DB_Persistence::getInstance();

		return reset($service->find(sprintf('slot = %s AND pos > %d AND article_id = %d AND clang = %d', $db->quote($this->getSlot()), $this->getPosition(), $this->getArticleId(), $this->getClang())));
	}

	public function getModule() {
		return $this->getSlice()->getModule();
	}

	public function setModule($module) {
		$slice = $this->getSlice();
		$slice->setModule($module);
		$this->slice = sly_Service_Factory::getSliceService()->save($slice);
	}

	/**
	 *
	 * @param sly_Model_Slice $slice
	 */
	public function setSlice(sly_Model_Slice $slice) {
		$this->slice = $slice;
		$this->slice_id = $slice->getId();
	}

	/**
	 *
	 * @param type $slot
	 */
	public function setSlot($slot) {
		$this->slot = $slot;
	}

	/**
	 *
	 * @param sly_Model_Article $article
	 */
	public function setArticle(sly_Model_Article $article) {
		$this->article = $article;
		$this->article_id = $article->getId();
		$this->clang = $article->getClang();
	}

	/**
	 * @param string $updateuser
	 */
	public function setUpdateUser($updateuser) {
		$this->updateuser = $updateuser;
	}

	/**
	 * @param mixed $updatedate  unix timestamp or date using 'YYYY-MM-DD HH:MM:SS' format
	 */
	public function setUpdateDate($updatedate) {
		$this->updatedate = sly_Util_String::isInteger($updatedate) ? (int) $updatedate : strtotime($updatedate);
	}

	/**
	 * @param string $createuser
	 */
	public function setCreateUser($createuser) {
		$this->createuser = $createuser;
	}

	/**
	 * @param mixed $createdate  unix timestamp or date using 'YYYY-MM-DD HH:MM:SS' format
	 */
	public function setCreateDate($createdate) {
		$this->createdate = sly_Util_String::isInteger($createdate) ? (int) $createdate : strtotime($createdate);
	}

	/**
	 *
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->pos = (int) $position;
	}

	/**
	 * @param  string $finder
	 * @param  string $value
	 */
	public function setValue($finder, $value = null) {
		$this->getSlice()->addValue($finder, $value);
	}

	public function setValues($values = array()) {
		return $this->getSlice()->setValues($values);
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @return mixed
	 */
	public function getValue($finder, $default = null) {
		return $this->getSlice()->getValue($finder, $default);
	}

	public function getValues() {
		return $this->getSlice()->getValues();
	}

	/**
	 * get the rendered output
	 *
	 * @return string
	 */
	public function getOutput() {
		$values   = $this->getValues();
		$renderer = new sly_Slice_Renderer($this->getModule(), $values);
		$output   = $renderer->renderOutput($this);
		return $output;
	}

	public function setRevision($revision) {
		$this->revision = (int) $revision;
	}

	/**
	 * drop slice from serialized instance
	 */
	public function __sleep() {
		$this->slice   = null;
		$this->article = null;
	}
}
