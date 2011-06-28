<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet eine Kategorie im Medienpool ab
 *
 * @ingroup redaxo2
 */
class OOMediaCategory {
	private $id = 0;
	private $parent_id = 0;
	private $name = '';
	private $path = '';
	private $createdate = 0;
	private $updatedate = 0;
	private $createuser = '';
	private $updateuser = '';
	private $children = array();
	private $files = array();
	private $revision = 0;

	protected function __construct() {
		/* empty by design */
	}

	/**
	 * @return OOMediaCategory
	 */
	public static function getCategoryById($id) {
		$id = (int) $id;
		if ($id <= 0) return null;

		$sql    = sly_DB_Persistence::getInstance();
		$result = $sql->magicFetch('file_category', '*', compact('id'));

		if ($result === false) {
			return null;
		}

		$cat = new self();

		$cat->id         = (int) $result['id'];
		$cat->parent_id  = (int) $result['re_id'];
		$cat->name       = $result['name'];
		$cat->path       = $result['path'];
		$cat->createdate = (int) $result['createdate'];
		$cat->updatedate = (int) $result['updatedate'];
		$cat->createuser = $result['createuser'];
		$cat->updateuser = $result['updateuser'];
		$cat->children   = null;
		$cat->files      = null;

		return $cat;
	}

	public static function getRootCategories() {
		return self::findCategories(array('re_id' => 0));
	}

	/**
	 * @return array
	 */
	public static function getCategoryByName($name) {
		return self::findCategories(array('name' => $name));
	}

	private static function findCategories($where) {
		$sql  = sly_DB_Persistence::getInstance();
		$cats = array();

		$sql->select('file_category', 'id', $where, null, 'name');
		foreach ($sql as $row) $cats[] = $row['id'];

		foreach ($cats as $idx => $id) {
			$cats[$idx] = self::getCategoryById($id);
		}

		return $cats;
	}

	public function __toString() {
		return 'OOMediaCategory, "'.$this->getId().'", "'.$this->getName().'"'."<br/>\n";
	}

	public function getId()         { return $this->id;         }
	public function getName()       { return $this->name;       }
	public function getPath()       { return $this->path;       }
	public function getUpdateUser() { return $this->updateuser; }
	public function getUpdateDate() { return $this->updatedate; }
	public function getCreateUser() { return $this->createuser; }
	public function getCreateDate() { return $this->createdate; }
	public function getParentId()   { return $this->parent_id;  }

	public function setName($name) {
		$this->name = $name;
	}

	public function getParent() {
		return self::getCategoryById($this->getParentId());
	}

	/**
	 * Get an array of all parentCategories.
	 * Returns an array of OOMediaCategory objects sorted by $prior.
	 */
	public function getParentTree() {
		$tree = array();

		if ($this->path) {
			$explode = explode('|', $this->path);

			if (is_array($explode)) {
				foreach ($explode as $var) {
					if (empty($var)) continue;
					$tree[] = self::getCategoryById($var);
				}
			}
		}

		return $tree;
	}

	/**
	 * Checks if $anObj is in the parent tree of the object
	 */
	public function inParentTree($anObj) {
		$tree = $this->getParentTree();

		foreach ($tree as $treeObj) {
			if ($treeObj == $anObj) {
				return true;
			}
		}

		return false;
	}

	public function getChildren() {
		if ($this->children === null) {
			$this->children = self::findCategories(array('re_id' => $this->getId()));
		}

		return $this->children;
	}

	public function countChildren() {
		return count($this->getChildren());
	}

	public function getMedia() {
		if ($this->files === null) {
			$this->files = array();

			$sql    = sly_DB_Persistence::getInstance();
			$result = array();
			$sql->select('file', 'id', array('category_id' => $this->getId()), null, 'updateDate DESC');
			foreach ($sql as $row) $result[] = $row['id'];
			foreach ($result as $id) {
				$this->files[] = OOMedia::getMediaById($id);
			}
		}

		return $this->files;
	}

	public function countMedia() {
		return count($this->getFiles());
	}

	public function isRootCategory() {
		return $this->hasParent() === false;
	}

	public function isParent($mediaCat) {
		if (is_int($mediaCat)) {
			return $mediaCat == $this->getParentId();
		}
		elseif (self::isValid($mediaCat)) {
			return $this->getParentId() == $mediaCat->getId();
		}

		return null;
	}

	public static function isValid($obj) {
		return $obj instanceof self;
	}

	public function hasParent() {
		return $this->getParentId() != 0;
	}

	public function hasChildren() {
		return count($this->getChildren()) > 0;
	}

	public function hasMedia() {
		return count($this->getMedia()) > 0;
	}

	/**
	 * @return boolean  returns <code>true</code> on success or <code>false</code> on error
	 */
	public function save() {
		$sql  = sly_DB_Persistence::getInstance();
		$data = array(
			're_id'    => $this->getParentId(),
			'name'     => $this->getName(),
			'path'     => $this->getPath(),
			'revision' => $this->revision
		);

		if ($this->getId() !== null) {
			$data['updatedate'] = time();
			$data['updateuser'] = sly_Util_User::getCurrentUser()->getLogin();

			$sql->update('file_category', $data, array('id' => $this->getId()));
		}
		else {
			$data['createdate'] = time();
			$data['createuser'] = sly_Util_User::getCurrentUser()->getLogin();

			$sql->insert('file_category', $data);
		}

		return true;
	}

	/**
	 * @return boolean  returns <code>true</code> on success or <code>false</code> on error
	 */
	public function delete($recurse = false) {
		// Rekursiv löschen?

		if (!$recurse && $this->hasChildren()) {
			return false;
		}

		if ($recurse) {
			$children = $this->getChildren();

			foreach ($children as $child) {
				if (!$child->delete($recurse)) return false;
			}
		}

		// Alle Dateien löschen

		if ($this->hasMedia()) {
			$files = $this->getMedia();

			foreach ($files as $file) {
				if (!$file->delete()) return false;
			}
		}

		$sql = sly_DB_Persistence::getInstance();
		$sql->delete('file_category', array('id' => $this->getId()));

		return true;
	}
}
