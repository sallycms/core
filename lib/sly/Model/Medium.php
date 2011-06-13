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
 * Business Model Klasse für Medien
 *
 * @author  christoph@webvariants.de
 * @ingroup model
 */
class sly_Model_Medium extends sly_Model_Base_Id {
	protected $updateuser;
	protected $category_id;
	protected $revision;
	protected $title;
	protected $createdate;
	protected $filename;
	protected $height;
	protected $width;
	protected $updatedate;
	protected $re_file_id;
	protected $createuser;
	protected $originalname;
	protected $attributes;
	protected $filetype;
	protected $filesize;

	protected $_attributes = array(
		'updateuser' => 'string', 'category_id' => 'int', 'revision' => 'int',
		'title' => 'string', 'createdate' => 'int', 'filename' => 'string',
		'height' => 'int', 'width' => 'int', 'updatedate' => 'int',
		're_file_id' => 'int', 'createuser' => 'string', 'originalname' => 'string',
		'attributes' => 'string', 'filetype' => 'string', 'filesize' => 'string'
	);

	public function getUpdateUser()   { return $this->updateuser;   }
	public function getCategoryId()   { return $this->category_id;  }
	public function getRevision()     { return $this->revision;     }
	public function getTitle()        { return $this->title;        }
	public function getCreateDate()   { return $this->createdate;   }
	public function getFilename()     { return $this->filename;     }
	public function getHeight()       { return $this->height;       }
	public function getWidth()        { return $this->width;        }
	public function getUpdateDate()   { return $this->updatedate;   }
	public function getReFileId()     { return $this->re_file_id;   }
	public function getCreateUser()   { return $this->createuser;   }
	public function getOriginalName() { return $this->originalname; }
	public function getAttributes()   { return $this->attributes;   }
	public function getFiletype()     { return $this->filetype;     }
	public function getFilesize()     { return $this->filesize;     }

	public function setUpdateUser($updateuser)     { $this->updateuser   = $updateuser;   }
	public function setCategoryId($category_id)    { $this->category_id  = $category_id;  }
	public function setRevision($revision)         { $this->revision     = $revision;     }
	public function setTitle($title)               { $this->title        = $title;        }
	public function setCreateDate($createdate)     { $this->createdate   = $createdate;   }
	public function setFilename($filename)         { $this->filename     = $filename;     }
	public function setHeight($height)             { $this->height       = $height;       }
	public function setWidth($width)               { $this->width        = $width;        }
	public function setUpdateDate($updatedate)     { $this->updatedate   = $updatedate;   }
	public function setReFileId($re_file_id)       { $this->re_file_id   = $re_file_id;   }
	public function setCreateUser($createuser)     { $this->createuser   = $createuser;   }
	public function setOriginalName($originalname) { $this->originalname = $originalname; }
	public function setAttributes($attributes)     { $this->attributes   = $attributes;   }
	public function setFiletype($filetype)         { $this->filetype     = $filetype;     }
	public function setFilesize($filesize)         { $this->filesize     = $filesize;     }
}
