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
				sly_Core::cache()->set('sly.medium', $id, $medium);
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

	public function add($filename, $title, $categoryID, $mimetype = null, $originalName = null) {
		// check file itself

		$filename = basename($filename);
		$fullname = SLY_MEDIAFOLDER.'/'.$filename;

		if (!file_exists($fullname)) {
			throw new sly_Exception('Can only add existing files.');
		}

		// check category

		$categoryID = (int) $categoryID;

		if (!sly_Util_MediaCategory::exists($categoryID)) {
			$categoryID = 0;
		}

		$size = @getimagesize($fullname);

		// finfo:             PHP >= 5.3, PECL fileinfo
		// mime_content_type: PHP >= 4.3 (deprecated)

		if (empty($mimetype)) {
			// if it's an image, we know the type
			if (isset($size['mime'])) {
				$mimetype = $size['mime'];
			}

			// or else try the new, recommended way
			elseif (function_exists('finfo_file')) {
				$finfo    = finfo_open(FILEINFO_MIME_TYPE);
				$mimetype = finfo_file($finfo, $fullname);
			}

			// argh, let's see if this old one exists
			elseif (function_exists('mime_content_type')) {
				$mimetype = mime_content_type($fullname);
			}

			// fallback to a generic type
			else {
				$mimetype = 'application/octet-stream';
			}
		}

		// create file object

		$file = new sly_Model_Medium();
		$file->setFiletype($mimetype);
		$file->setTitle($title);
		$file->setOriginalName($originalName === null ? $filename : basename($originalName));
		$file->setFilename($filename);
		$file->setFilesize(filesize($fullname));
		$file->setCategoryId((int) $categoryID);
		$file->setRevision(0); // totally useless...
		$file->setReFileId(0); // even more useless
		$file->setCreateColumns();

		if ($size) {
			$file->setWidth($size[0]);
			$file->setHeight($size[1]);
		}

		// store and return it

		$this->save($file);
		sly_Core::dispatcher()->notify('SLY_MEDIA_ADDED', $file);
		return $file;
	}

	public function delete($mediumID) {
		$medium = $this->findById($mediumID);

		try {
			$sql = sly_DB_Persistence::getInstance();
			$sql->delete('file', array('id' => $medium->getId()));

			if ($medium->exists()) {
				unlink(SLY_MEDIAFOLDER.'/'.$medium->getFilename());
			}
		}
		catch (Exception $e) {
			throw new sly_Exception('Cannot delete medium: '.$e->getMessage());
		}

		sly_Core::cache()->delete('sly.medium', $medium->getId());
		sly_Core::dispatcher()->notify('SLY_MEDIA_DELETED', $medium);

		return true;
	}
}
