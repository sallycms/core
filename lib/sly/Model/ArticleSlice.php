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
	 * @param  sly_Service_Article $service
	 * @return sly_Model_Article
	 */
	public function getArticle(sly_Service_Article $service = null) {
		if (empty($this->article)) {
			$service       = $service ?: sly_Core::getContainer()->getArticleService();
			$this->article = $service->findByPK($this->getArticleId(), $this->getClang(), $this->getRevision());
		}

		return $this->article;
	}

	/**
	 *
	 * @param  sly_Service_Slice $service
	 * @return sly_Model_Slice
	 */
	public function getSlice(sly_Service_Slice $service = null) {
		if (empty($this->slice)) {
			$service     = $service ?: sly_Core::getContainer()->getSliceService();
			$this->slice = $service->findById($this->getSliceId());
		}

		return $this->slice;
	}

	/**
	 *
	 * @param  sly_Service_ArticleSlice $service
	 * @return sly_Model_ArticleSlice
	 */
	public function getPrevious(sly_Service_ArticleSlice $service = null) {
		$service = $service ?: sly_Core::getContainer()->getArticleSliceService();

		return $service->getPrevious($this);
	}

	/**
	 *
	 * @param  sly_Service_ArticleSlice $service
	 * @return sly_Model_ArticleSlice
	 */
	public function getNext(sly_Service_ArticleSlice $service = null) {
		$service = $service ?: sly_Core::getContainer()->getArticleSliceService();

		return $service->getNext($this);
	}

	public function getModule() {
		return $this->getSlice()->getModule();
	}

	/**
	 * set the module on the associated slice and save the change immediately
	 *
	 * @param string            $module
	 * @param sly_Service_Slice $service
	 */
	public function setModule($module, sly_Service_Slice $service = null) {
		$service = $service ?: sly_Core::getContainer()->getSliceService();
		$slice   = $this->getSlice();

		$slice->setModule($module);

		$this->slice = $service->save($slice);
	}

	/**
	 *
	 * @param sly_Model_Slice $slice
	 */
	public function setSlice(sly_Model_Slice $slice) {
		$this->slice    = $slice;
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
		$this->article    = $article;
		$this->article_id = $article->getId();
		$this->clang      = $article->getClang();
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
		$this->getSlice()->setValue($finder, $value);
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
	 * render (execute) this slice's module
	 *
	 * This is re-implemented here (instead of just proxying to
	 * model_slice->getOutput()), so that the executed module can use the
	 * additional API on this class.
	 *
	 * @param  sly_Slice_Renderer $renderer
	 * @return string
	 */
	public function getOutput(sly_Slice_Renderer $renderer = null) {
		$renderer = $renderer ?: sly_Core::getContainer()->get('sly-slice-renderer');

		return $renderer->renderOutput($this);
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
