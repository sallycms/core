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
 * Business Model Klasse für Mediumkategorien
 *
 * @author  christoph@webvariants.de
 * @ingroup model
 */
class sly_Model_MediaCategory extends sly_Model_Base_Id {
	protected $name;       ///< string
	protected $re_id;      ///< int
	protected $path;       ///< string
	protected $createuser; ///< string
	protected $createdate; ///< int
	protected $updateuser; ///< string
	protected $updatedate; ///< int
	protected $attributes; ///< string
	protected $revision;   ///< int

	protected $_attributes = array(
		'name' => 'string', 're_id' => 'int', 'path' => 'string', 'createuser' => 'string',
		'createdate' => 'int', 'updateuser' => 'string', 'updatedate' => 'int',
		'attributes' => 'string', 'revision' => 'int'
	); ///< array

	public function setName($name)             { $this->name       = $name;       } ///< @param string $name
	public function setParentId($re_id)        { $this->re_id      = $re_id;      } ///< @param int    $re_id
	public function setPath($path)             { $this->path       = $path;       } ///< @param string $path
	public function setCreateDate($createdate) { $this->createdate = $createdate; } ///< @param int    $createdate
	public function setUpdateDate($updatedate) { $this->updatedate = $updatedate; } ///< @param int    $updatedate
	public function setCreateUser($createuser) { $this->createuser = $createuser; } ///< @param string $createuser
	public function setUpdateUser($updateuser) { $this->updateuser = $updateuser; } ///< @param string $updateuser
	public function setAttributes($attributes) { $this->attributes = $attributes; } ///< @param string $attributes
	public function setRevision($revision)     { $this->revision   = $revision;   } ///< @param int    $revision

	public function getName()       { return $this->name;       } ///< @return string
	public function getParentId()   { return $this->re_id;      } ///< @return int
	public function getPath()       { return $this->path;       } ///< @return string
	public function getCreateDate() { return $this->createdate; } ///< @return int
	public function getUpdateDate() { return $this->updatedate; } ///< @return int
	public function getCreateUser() { return $this->createuser; } ///< @return string
	public function getUpdateUser() { return $this->updateuser; } ///< @return string
	public function getAttributes() { return $this->attributes; } ///< @return string
	public function getRevision()   { return $this->revision;   } ///< @return int

	/**
	 * @return sly_Model_MediaCategory
	 */
	public function getParent() {
		return sly_Util_MediaCategory::findById($this->re_id);
	}

	/**
	 * @return array
	 */
	public function getChildren() {
		return sly_Util_MediaCategory::findByParentId($this->id);
	}

	/**
	 * @return boolean
	 */
	public function isRootCategory() {
		return $this->re_id === 0;
	}

	/**
	 * @param  boolean $asInstances
	 * @return array
	 */
	public function getParentTree($asInstances = true) {
		$list = array();

		if ($this->path) {
			$list = array_filter(explode('|', $this->path));

			if ($asInstances) {
				foreach ($list as $idx => $catID) {
					$list[$idx] = sly_Util_MediaCategory::findById($catID);
				}
			}
		}

		return $list;
	}

	/**
	 * @param  mixed $reference
	 * @return boolean
	 */
	public function inParentTree($reference) {
		$ref  = $reference instanceof self ? $reference->getId() : (int) $reference;
		$list = $this->getParentTree(false);

		return in_array($ref, $list);
	}

	/**
	 * @return array
	 */
	public function getMedia() {
		return sly_Util_Medium::findByCategory($this->id);
	}
}
