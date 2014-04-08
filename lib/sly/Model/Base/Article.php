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
	protected $clang;       ///< int
	protected $revision;    ///< int
	protected $latest;      ///< int
	protected $online;      ///< int
	protected $deleted;     ///< int
	protected $type;        ///< string
	protected $re_id;       ///< int
	protected $path;        ///< string
	protected $pos;         ///< int
	protected $name;        ///< string
	protected $catpos;      ///< int
	protected $catname;     ///< string
	protected $startpage;   ///< int
	protected $createdate;  ///< int
	protected $updatedate;  ///< int
	protected $createuser;  ///< string
	protected $updateuser;  ///< string
	protected $attributes;  ///< string

	protected $_pk = array('id' => 'int', 'clang' => 'int', 'revision' => 'int'); ///< array
	protected $_attributes = array(
		'latest'     => 'int',
		'online'     => 'int',
		'deleted'    => 'int',
		'type'       => 'string',
		're_id'      => 'int',
		'path'       => 'string',
		'pos'        => 'int',
		'name'       => 'string',
		'catpos'     => 'int',
		'catname'    => 'string',
		'startpage'  => 'int',
		'createdate' => 'datetime',
		'updatedate' => 'datetime',
		'createuser' => 'string',
		'updateuser' => 'string',
		'attributes' => 'string'
	); ///< array

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
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
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @return boolean
	 */
	public function isLatest() {
		return $this->latest ? true : false;
	}

	/**
	 * @return boolean
	 */
	public function isOnline() {
		return $this->online ? true : false;
	}

	/**
	 * @return boolean
	 */
	public function isOffline() {
		return!$this->isOnline();
	}

	/**
	 * @return boolean
	 */
	public function isDeleted() {
		return $this->deleted ? true : false;
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
	public function getParentId() {
		return $this->re_id;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
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
	public function getCreateDate() {
		return $this->createdate;
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
	public function getUpdateUser() {
		return $this->updateuser;
	}

	/**
	 * @return string
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = (int) $id;
	}

	/**
	 * @param int $clang
	 */
	public function setClang($clang) {
		$this->clang = (int) $clang;
	}

	/**
	 * @param boolean $isLatest
	 */
	public function setLatest($isLatest) {
		$this->latest = $isLatest ? 1 : 0;
	}

	/**
	 * @param boolean $online
	 */
	public function setOnline($online) {
		$this->online = $online ? 1 : 0;
	}

	/**
	 * @param boolean $deleted
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted ? 1 : 0;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = (string) $type;
	}

	/**
	 * @param int $re_id
	 */
	public function setParentId($re_id) {
		$this->re_id = (int) $re_id;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->pos = (int) $position;
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
	public function setCreateDate($createdate) {
		$this->createdate = sly_Util_String::isInteger($createdate) ? (int) $createdate : strtotime($createdate);
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
	 * @param string $updateuser
	 */
	public function setUpdateUser($updateuser) {
		$this->updateuser = $updateuser;
	}

	/**
	 * @param string $attributes
	 */
	public function setAttributes($attributes) {
		$this->attributes = $attributes;
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
	public function getParentTree($asObjects = true) {
		$explode = explode('|', $this->getPath());
		$explode = array_filter($explode);

		if ($this->getStartpage() == 1) {
			$explode[] = $this->getId();
		}

		if (!$asObjects) {
			return $explode;
		}

		$return     = array();
		$catService = sly_Core::getContainer()->getCategoryService();

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
		return in_array($anObj->getId(), $this->getParentTree(false), true);
	}
}
