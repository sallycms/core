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
 * Business Model Klasse für Artikel
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Base_Article extends sly_Model_Base {
	protected $id;          ///< int
	protected $updateuser;  ///< string
	protected $status;      ///< int
	protected $name;        ///< string
	protected $catpos;      ///< int
	protected $createdate;  ///< int
	protected $clang;       ///< int
	protected $re_id;       ///< int
	protected $pos;         ///< int
	protected $catname;     ///< string
	protected $startpage;   ///< int
	protected $updatedate;  ///< int
	protected $createuser;  ///< string
	protected $attributes;  ///< string
	protected $path;        ///< string
	protected $type;        ///< string
	protected $revision;    ///< int

	protected $_pk = array('id' => 'int', 'clang' => 'int'); ///< array
	protected $_attributes = array(
		'updateuser' => 'string', 'status' => 'int', 'name' => 'string',
		'catpos' => 'int', 'createdate' => 'datetime', 're_id' => 'int', 'pos' => 'int',
		'catname' => 'string', 'startpage' => 'int', 'updatedate' => 'datetime',
		'createuser' => 'string', 'attributes' => 'string', 'path' => 'string',
		'type' => 'string', 'revision' => 'int'
	); ///< array

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getUpdateUser() {
		return $this->updateuser;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getCatPosition() {
		return $this->catpos;
	}

	/**
	 * @deprecated  since 0.6
	 */
	public function getCatPrior() {
		return $this->catpos;
	}

	/**
	 * @return int
	 */
	public function getCreateDate() {
		return $this->createdate;
	}

	/**
	 * @return int
	 */
	public function getClang() {
		return $this->clang;
	}

	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->re_id;
	}

	/**
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
	 * @return string
	 */
	public function getCatName() {
		return $this->catname;
	}

	/**
	 * @return int
	 */
	public function getStartpage() {
		return $this->startpage;
	}

	/**
	 * @return int
	 */
	public function getUpdateDate() {
		return $this->updatedate;
	}

	/**
	 * @return string
	 */
	public function getCreateUser() {
		return $this->createuser;
	}

	/**
	 * @return string
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = (int) $id;
	}

	/**
	 * @param string $updateuser
	 */
	public function setUpdateUser($updateuser) {
		$this->updateuser = $updateuser;
	}

	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = (int) $status;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @param int $position
	 */
	public function setCatPosition($position) {
		$this->catpos = (int) $position;
	}

	/**
	 * @deprecated  since 0.6
	 */
	public function setCatPrior($position) {
		$this->catpos = (int) $position;
	}

	/**
	 * @param mixed $updatedate  unix timestamp or date using 'YYYY-MM-DD HH:MM:SS' format
	 */
	public function setCreateDate($createdate) {
		$this->createdate = sly_Util_String::isInteger($createdate) ? (int) $createdate : strtotime($createdate);
	}

	/**
	 * @param int $clang
	 */
	public function setClang($clang) {
		$this->clang = (int) $clang;
	}

	/**
	 * @param int $re_id
	 */
	public function setParentId($re_id) {
		$this->re_id = (int) $re_id;
	}

	/**
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->pos = (int) $position;
	}

	/**
	 * @deprecated  since 0.6
	 */
	public function setPrior($position) {
		$this->catpos = (int) $position;
	}

	/**
	 * @param string $catname
	 */
	public function setCatName($catname) {
		$this->catname = $catname;
	}

	/**
	 * @param int $startpage
	 */
	public function setStartpage($startpage) {
		$this->startpage = (int) $startpage;
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
	 * @param string $attributes
	 */
	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @param int $revision
	 */
	public function setRevision($revision) {
		$this->revision = (int) $revision;
	}

	/**
	 * @return boolean
	 */
	public function isOnline() {
		return $this->getStatus() == 1;
	}

	/**
	 * @return boolean
	 */
	public function isOffline() {
		return!$this->isOnline();
	}

	/**
	 * return the url
	 *
	 * @param  mixed   $params
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
