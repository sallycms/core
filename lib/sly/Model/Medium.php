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
 * Business Model Klasse für Medien
 *
 * @author  christoph@webvariants.de
 * @ingroup model
 */
class sly_Model_Medium extends sly_Model_Base_Id {
	protected $updateuser;   ///< string
	protected $category_id;  ///< int
	protected $revision;     ///< int
	protected $title;        ///< string
	protected $createdate;   ///< int
	protected $filename;     ///< string
	protected $height;       ///< int
	protected $width;        ///< int
	protected $updatedate;   ///< int
	protected $re_file_id;   ///< int
	protected $createuser;   ///< string
	protected $originalname; ///< string
	protected $attributes;   ///< string
	protected $filetype;     ///< string
	protected $filesize;     ///< int

	protected $_attributes = array(
		'updateuser' => 'string', 'category_id' => 'int', 'revision' => 'int',
		'title' => 'string', 'createdate' => 'int', 'filename' => 'string',
		'height' => 'int', 'width' => 'int', 'updatedate' => 'int',
		're_file_id' => 'int', 'createuser' => 'string', 'originalname' => 'string',
		'attributes' => 'string', 'filetype' => 'string', 'filesize' => 'string'
	); ///< array

	public function getUpdateUser()   { return $this->updateuser;   } ///< @return string
	public function getCategoryId()   { return $this->category_id;  } ///< @return int
	public function getRevision()     { return $this->revision;     } ///< @return int
	public function getTitle()        { return $this->title;        } ///< @return string
	public function getCreateDate()   { return $this->createdate;   } ///< @return int
	public function getFilename()     { return $this->filename;     } ///< @return string
	public function getHeight()       { return $this->height;       } ///< @return int
	public function getWidth()        { return $this->width;        } ///< @return int
	public function getUpdateDate()   { return $this->updatedate;   } ///< @return int
	public function getReFileId()     { return $this->re_file_id;   } ///< @return int
	public function getCreateUser()   { return $this->createuser;   } ///< @return string
	public function getOriginalName() { return $this->originalname; } ///< @return string
	public function getAttributes()   { return $this->attributes;   } ///< @return string
	public function getFiletype()     { return $this->filetype;     } ///< @return string
	public function getFilesize()     { return $this->filesize;     } ///< @return int

	public function setUpdateUser($updateuser)     { $this->updateuser   = $updateuser;   } ///< @param string $updateuser
	public function setCategoryId($category_id)    { $this->category_id  = $category_id;  } ///< @param int    $category_id
	public function setRevision($revision)         { $this->revision     = $revision;     } ///< @param int    $revision
	public function setTitle($title)               { $this->title        = $title;        } ///< @param string $title
	public function setCreateDate($createdate)     { $this->createdate   = $createdate;   } ///< @param int    $createdate
	public function setFilename($filename)         { $this->filename     = $filename;     } ///< @param string $filename
	public function setHeight($height)             { $this->height       = $height;       } ///< @param int    $height
	public function setWidth($width)               { $this->width        = $width;        } ///< @param int    $width
	public function setUpdateDate($updatedate)     { $this->updatedate   = $updatedate;   } ///< @param int    $updatedate
	public function setReFileId($re_file_id)       { $this->re_file_id   = $re_file_id;   } ///< @param int    $re_file_id
	public function setCreateUser($createuser)     { $this->createuser   = $createuser;   } ///< @param string $createuser
	public function setOriginalName($originalname) { $this->originalname = $originalname; } ///< @param string $originalname
	public function setAttributes($attributes)     { $this->attributes   = $attributes;   } ///< @param string $attributes
	public function setFiletype($filetype)         { $this->filetype     = $filetype;     } ///< @param string $filetype
	public function setFilesize($filesize)         { $this->filesize     = $filesize;     } ///< @param int    $filesize

	/**
	 * @return sly_Model_MediaCategory
	 */
	public function getCategory() {
		$service = sly_Service_Factory::getMediaCategoryService();
		return $service->findById($this->category_id);
	}

	/**
	 * @return string
	 */
	public function getFormattedSize() {
		return sly_Util_String::formatFilesize($this->filesize);
	}

	/**
	 * @return string
	 */
	public function getExtension() {
		return substr(strrchr($this->filename, '.'), 1);
	}

	/**
	 * @return boolean
	 */
	public function exists() {
		return strlen($this->filename) > 0 && file_exists(SLY_MEDIAFOLDER.'/'.$this->filename);
	}

	/**
	 * @return string
	 */
	public function getFullPath() {
		return SLY_MEDIAFOLDER.'/'.$this->filename;
	}
}
