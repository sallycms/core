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
 * Business Model Klasse für Artikel
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Base_Article extends sly_Model_Base {

	protected $id;
	protected $updateuser;
	protected $status;
	protected $name;
	protected $catprior;
	protected $createdate;
	protected $clang;
	protected $re_id;
	protected $prior;
	protected $catname;
	protected $startpage;
	protected $updatedate;
	protected $createuser;
	protected $attributes;
	protected $path;
	protected $type;
	protected $revision;
	protected $_pk = array('id' => 'int', 'clang' => 'int');
	protected $_attributes = array(
		'updateuser' => 'string', 'status' => 'int', 'name' => 'string',
		'catprior' => 'int', 'createdate' => 'int',
		're_id' => 'int', 'prior' => 'int',
		'catname' => 'string', 'startpage' => 'int', 'updatedate' => 'int',
		'createuser' => 'string', 'attributes' => 'string', 'path' => 'string',
		'type' => 'string', 'revision' => 'int'
	);

	public function getId() {
		return $this->id;
	}

	public function getUpdateuser() {
		return $this->updateuser;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getName() {
		return $this->name;
	}

	public function getCatprior() {
		return $this->catprior;
	}

	public function getCreatedate() {
		return $this->createdate;
	}

	public function getClang() {
		return $this->clang;
	}

	public function getParentId() {
		return $this->re_id;
	}

	public function getPrior() {
		return $this->prior;
	}

	public function getCatname() {
		return $this->catname;
	}

	public function getStartpage() {
		return $this->startpage;
	}

	public function getUpdatedate() {
		return $this->updatedate;
	}

	public function getCreateuser() {
		return $this->createuser;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function getPath() {
		return $this->path;
	}

	public function getType() {
		return $this->type;
	}

	public function getRevision() {
		return $this->revision;
	}

	public function setId($id) {
		$this->id = intval($id);
	}

	public function setUpdateuser($updateuser) {
		$this->updateuser = $updateuser;
	}

	public function setStatus($status) {
		$this->status = $status;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setCatprior($catprior) {
		$this->catprior = $catprior;
	}

	public function setCreatedate($createdate) {
		$this->createdate = $createdate;
	}

	public function setClang($clang) {
		$this->clang = $clang;
	}

	public function setParentId($re_id) {
		$this->re_id = $re_id;
	}

	public function setPrior($prior) {
		$this->prior = $prior;
	}

	public function setCatname($catname) {
		$this->catname = $catname;
	}

	public function setStartpage($startpage) {
		$this->startpage = $startpage;
	}

	public function setUpdatedate($updatedate) {
		$this->updatedate = $updatedate;
	}

	public function setCreateuser($createuser) {
		$this->createuser = $createuser;
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function setType($type) {
		$this->type = $type;
	}

	public function setRevision($revision) {
		$this->revision = $revision;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isOnline() {
		return $this->getStatus() == 1;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isOffline() {
		return!$this->isOnline();
	}

	/**
	 * return the url
	 *
	 * @param  array   $params
	 * @param  string  $divider
	 * @param  boolean $disableCache
	 * @return string
	 */
	public function getUrl($params = '', $divider = '&amp;', $disableCache = false) {
		static $urlCache = array();

		$id    = $this->getId();
		$clang = $this->getClang();

		// cache the URLs for this request (unlikely to change)

		$cacheKey = substr(md5($id.'_'.$clang.'_'.json_encode($params).'_'.$divider), 0, 10);

		if (!$disableCache && isset($urlCache[$cacheKey])) {
			return $urlCache[$cacheKey];
		}

		// check for any fancy URL addOns

		$paramString = sly_Util_HTTP::queryString($params, $divider);
		$dispatcher  = sly_Core::dispatcher();
		$url         = $dispatcher->filter('URL_REWRITE', '', array(
			'id'            => $id,
			'clang'         => $clang,
			'params'        => $paramString,
			'divider'       => $divider,
			'disable_cache' => $disableCache
		));

		// if no listener is available, generate plain index.php?article_id URLs

		if (empty($url)) {
			$clangString  = '';
			$multilingual = sly_Util_Language::isMultilingual();

			if ($multilingual && $clang != sly_Core::getDefaultClangId()) {
				$clangString = $divider.'clang='.$clang;
			}

			$url = 'index.php?article_id='.$id.$clangString.$paramString;
		}

		$urlCache[$cacheKey] = $url;
		return $url;
	}

	/**
	 *
	 * @return array
	 */
	public function getParentTree() {
		$return = array();

		$explode = explode('|', $this->getPath());
		$explode = array_filter($explode);

		if ($this->getStartpage() == 1) {
			$explode[] = $this->getId();
		}
		foreach ($explode as $var) {
			$return[] = sly_Util_Category::findById($var, $this->getClang());
		}
		return $return;
	}

	/**
	 *
	 * @param  sly_Model_Base_Article $anObj
	 * @return boolean
	 */
	public function inParentTree(sly_Model_Base_Article $anObj) {
		$tree = $this->getParentTree();

		foreach ($tree as $treeObj) {
			if ($treeObj->getId() == $anObj->getId()) {
				return true;
			}
		}

		return false;
	}

}
