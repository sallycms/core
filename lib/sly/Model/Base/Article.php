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
 * Business Model Klasse für Artikel
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Base_Article extends sly_Model_Base {
	protected $id;          ///< int
	protected $updateuser;  ///< string
	protected $online;      ///< boolean
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
	protected $deleted;     ///< int

	protected $_pk = array('id' => 'int', 'clang' => 'int', 'revision' => 'int'); ///< array
	protected $_attributes = array(
		'updateuser' => 'string', 'name' => 'string',
		'catpos' => 'int', 'createdate' => 'datetime', 're_id' => 'int', 'pos' => 'int',
		'catname' => 'string', 'startpage' => 'int', 'updatedate' => 'datetime',
		'createuser' => 'string', 'attributes' => 'string', 'path' => 'string',
		'type' => 'string', 'deleted' => 'int'
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
		$this->type = (string) $type;
	}

	/**
	 * @param int $revision
	 */
	public function setRevision($revision) {
		$this->revision = (int) $revision;
	}

	/**
	 *
	 * @param boolean $online
	 */
	public function setOnline($online) {
		$this->online = (boolean) $online;
	}

	/**
	 * @return boolean
	 */
	public function isOnline() {
		return $this->online;
	}

	/**
	 * @return boolean
	 */
	public function isOffline() {
		return!$this->isOnline();
	}

	public function isDeleted() {
		return $this->deleted == 1;
	}

	/**
	 * return the url
	 *
	 * @param  mixed               $params
	 * @param  string              $divider
	 * @param  boolean             $disableCache
	 * @param  sly_Service_Article $service
	 * @return string
	 */
	public function getUrl($params = '', $divider = '&amp;', $disableCache = false, sly_Service_Article $service = null) {
		$service = $service ?: sly_Core::getContainer()->getArticleService();

		return $service->getUrl($this, $params, $divider, $disableCache);
	}

	/**
	 * @return array
	 */
	public function getParentTree() {
		$return     = array();
		$catService = sly_Core::getContainer()->getCategoryService();

		$explode = explode('|', $this->getPath());
		$explode = array_filter($explode);

		if ($this->getStartpage() == 1) {
			$explode[] = $this->getId();
		}

		foreach ($explode as $var) {
			$return[] = $catService->findByPK($var, $this->getClang());
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
