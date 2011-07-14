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
 * DB Model Klasse für Medien
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Medium extends sly_Service_Model_Base_Id {
	protected $tablename = 'file';

	protected function makeInstance(array $params) {
		return new sly_Model_Medium($params);
	}

	public function findById($id) {
		$id = (int) $id;

		if ($id === 0) {
			return null;
		}

		$medium = sly_Core::cache()->get('sly.medium', $id, null);

		if ($medium === null) {
			$medium = $this->findOne(array('id' => $id));

			if ($medium !== null) {
				sly_Core::cache()->set('sly.medium', $id, $article);
			}
		}

		return $medium;
	}

	public function findByFilename($filename) {
		$hash = md5($filename);
		$id   = sly_Core::cache()->get('sly.medium', $hash, null);

		if ($id === null) {
			$db = sly_DB_Persistence::getInstance();
			$id = $db->magicFetch('file', 'id', array('filename' => $filename));

			if ($id === false) {
				return null;
			}

			sly_Core::cache()->set('sly.medium', $hash, $id);
		}

		return $this->findById($id);
	}

	public function findMediaByExtension($extension) {
		$namespace = 'sly.medium.list';
		$list      = sly_Core::cache()->get($namespace, $extension, null);

		if ($list === null) {
			$sql  = sly_DB_Persistence::getInstance();
			$list = array();

			$sql->select('file', 'id', array('SUBSTRING(filename, LOCATE(".", filename) + 1)' => $extension), null, 'filename');
			foreach ($sql as $row) $list[] = $row['id'];

			sly_Core::cache()->set($namespace, $extension, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	public function findMediaByCategory($categoryId) {
		$categoryId = (int) $categoryId;
		$namespace  = 'sly.medium.list';
		$list       = sly_Core::cache()->get($namespace, $categoryId, null);

		if ($list === null) {
			$list  = array();
			$sql   = sly_DB_Persistence::getInstance();
			$where = array('category_id' => $categoryId);

			$sql->select('file', 'id', $where, null, 'filename');
			foreach ($sql as $row) $list[] = (int) $row['id'];

			sly_Core::cache()->set($namespace, $categoryId, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}
}
