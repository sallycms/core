<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet ein Medium des Medienpools ab
 *
 * @ingroup redaxo2
 */
class OOMedia {
	private $id = '';
	private $cat_id = '';
	private $cat_name = '';
	private $cat = '';
	private $name = '';
	private $orgname = '';
	private $type = '';
	private $size = '';
	private $width = '';
	private $height = '';
	private $title = '';
	private $updatedate = '';
	private $createdate = '';
	private $updateuser = '';
	private $createuser = '';

	private static $dummeFileSize = null;

	protected function __construct($id = null) {
		/* empty by design */
	}

	/**
	 * @return OOMedia
	 */
	public static function getMediaById($id) {
		$id = (int) $id;
		if ($id <= 0) return null;

		$media = sly_Core::cache()->get('sly.medium', $id, null);

		if ($media === null) {
			$sql    = sly_DB_Persistence::getInstance();
			$result = $sql->magicFetch('file', '*', compact('id'));

			if ($result === false) {
				return null;
			}

			$result['catname'] = $sql->magicFetch('file_category', 'name', array('id' => $result['category_id']));

			static $aliasMap = array(
				'category_id'  => 'cat_id',
				'catname'      => 'cat_name',
				'filename'     => 'name',
				'originalname' => 'orgname',
				'filetype'     => 'type',
				'filesize'     => 'size'
	      );

	      $media = new OOMedia();

	      foreach (array_keys($result) as $fieldName) {
	      	if (in_array($fieldName, array_keys($aliasMap))) {
					$var_name = $aliasMap[$fieldName];
				}
	      	else {
					$var_name = $fieldName;
				}

	      	$media->$var_name = $result[$fieldName];
	      }

	      sly_Core::cache()->set('sly.medium', $id, $media);
		}

		return $media;
	}

	public static function getMediaByName($filename) {
		return self::getMediaByFileName($filename);
	}

	/**
	 * @example OOMedia::getMediaByExtension('css');
	 * @example OOMedia::getMediaByExtension('gif');
	 */
	public static function getMediaByExtension($extension) {
		$sql   = sly_DB_Persistence::getInstance();
		$media = array();

		$sql->select('file', 'id', array('SUBSTRING(filename, LOCATE(".", filename) + 1)' => $extension));
		foreach ($sql as $row) $media[] = $row['id'];

		foreach ($media as $idx => $id) {
			$media[$idx] = self::getMediaById($id);
		}

		return $media;
	}

	/**
	 * @return OOMedia
	 */
	public static function getMediaByFileName($name) {
		$sql    = sly_DB_Persistence::getInstance();
		$result = $sql->magicFetch('file', 'id', array('filename' => $name));

		return $result === false ? null : self::getMediaById($result);
	}

	public function getCategory() {
		if ($this->cat === null) {
			$this->cat = OOMediaCategory::getCategoryById($this->getCategoryId());
		}

		return $this->cat;
	}

	public function getId()           { return $this->id;         }
	public function getCategoryName() { return $this->cat_name;   }
	public function getCategoryId()   { return $this->cat_id;     }
	public function getTitle()        { return $this->title;      }
	public function getFileName()     { return $this->name;       }
	public function getOrgFileName()  { return $this->orgname;    }
	public function getWidth()        { return $this->width;      }
	public function getHeight()       { return $this->height;     }
	public function getType()         { return $this->type;       }
	public function getSize()         { return $this->size;       }
	public function getUpdateUser()   { return $this->updateuser; }
	public function getCreateUser()   { return $this->createuser; }

	public function getPath() {
		return SLY_MEDIAFOLDER;
	}

	public function getFullPath() {
		return $this->getPath().'/'.$this->getFileName();
	}

	public function getFormattedSize() {
		return sly_Util_String::formatFilesize($this->getSize());
	}

	/**
	 * Formats a datestamp with the given format.
	 *
	 * If format is <code>null</code> the datestamp is returned.
	 *
	 * If format is <code>''</code> the datestamp is formated
	 * with the default <code>dateformat</code> (lang-files).
	 */
	private static function getDate($date, $format = null) {
		if ($format !== null) {
			if ($format == '') {
				// TODO Im Frontend gibts kein I18N
				// $format = t('dateformat');
				$format = '%a %d. %B %Y';
			}

			return sly_Util_String::formatStrftime($format, $date);
		}

		return $date;
	}

	public function getUpdateDate($format = null) {
		return self::getDate($this->updatedate, $format);
	}

	public function getCreateDate($format = null) {
		return self::getDate($this->createdate, $format);
	}

	public function toImage($params = array()) {
		$params = sly_makeArray($params);
		$path   = SLY_BASE;

		if (isset($params['path'])) {
			$path = $params['path'];
			unset($params['path']);
		}

		// Ist das Medium ein Bild?
		// Falls nicht, verwenden wir von hier ab das Dummy-Icon.

		if (!$this->isImage()) {
			$path = 'assets/';
			$file = 'file_dummy.png';

			if (self::$dummeFileSize === null) {
				self::$dummeFileSize = getimagesize($path.$file);
			}

			if (self::$dummeFileSize) {
				$params['width']  = self::$dummeFileSize[0];
				$params['height'] = self::$dummeFileSize[1];
			}
		}
		else {
			$resize = false;

			// ResizeModus festlegen
			if (isset ($params['resize']) && $params['resize']) {
				unset ($params['resize']);

				$service = sly_Service_Factory::getAddOnService();

				// Resize Addon installiert?
				if ($service->isAvailable('image_resize')) {
					$resize = true;

					if (isset($params['width'])) {
						$resizeMode  = 'w';
						$resizeParam = $params['width'];
						unset($params['width']);
					}
					elseif (isset($params['height'])) {
						$resizeMode  = 'h';
						$resizeParam = $params['height'];
						unset($params['height']);
					}
					elseif (isset($params['crop'])) {
						$resizeMode  = 'c';
						$resizeParam = $params['crop'];
						unset($params['crop']);
					}
					else {
						$resizeMode  = 'a';
						$resizeParam = 100;
					}

					// evtl. Größeneinheiten entfernen
					$resizeParam = str_replace(array('px', 'pt',  '%', 'em'), '', $resizeParam);
				}
			}

			// Bild resizen?
			if ($resize) {
				$file = '../imageresize/'.$resizeParam.$resizeMode.'__'.$this->getFileName();
			}
			else {
				// Bild 1:1 anzeigen
				$path .= 'data/mediapool/';
				$file = $this->getFileName();
			}
		}

		$title = $this->getTitle();

		// Alternativtext hinzufügen

		if (!isset($params['alt'])) {
			$params['alt'] = $title;
		}

		// Titel hinzufügen

		if (!isset($params['title']) && $title != '') {
			$params['title'] = $title;
		}

		$params['src'] = $path.$file;
		return sprintf('<img %s />', sly_Util_HTML::buildAttributeString($params, array('alt')));
	}

	public function toLink($attributes = '') {
		return sprintf('<a href="%s" title="%s"%s>%s</a>', $this->getFullPath(), $this->getDescription(), $attributes, $this->getFileName());
	}

	public function toIcon($attributes = array()) {
		if (!isset($attributes['alt']))   $attributes['alt']   = '"'.$this->getExtension().'"-Symbol';
		if (!isset($attributes['title'])) $attributes['title'] = $attributes['alt'];
		if (!isset($attributes['style'])) $attributes['style'] = 'width:44px;height:38px';

		$attributes['src'] = $this->getIcon();
		return sprintf('<img %s />', sly_Util_HTML::buildAttributeString($attributes, array('alt')));
	}

	public static function isValid($media) {
		return $media instanceof self;
	}

	public function isImage() {
		return self::_isImage($this->getFileName());
	}

	public static function _isImage($filename) {
		static $imageExtensions = array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		return in_array(self::_getExtension($filename), $imageExtensions);
	}

	public function isInUse() {
		$sql      = sly_DB_Persistence::getInstance();
		$filename = addslashes($this->getFileName());
		$prefix   = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$query    =
			'SELECT s.article_id, s.clang FROM '.$prefix.'slice_value sv, '.$prefix.'article_slice s, '.$prefix.'article a '.
			'WHERE sv.slice_id = s.slice_id AND a.id = s.article_id AND a.clang = s.clang AND ('.
			'(sv.type = "'.rex_var_media::MEDIALIST.'" AND (value LIKE "'.$filename.',%" OR value LIKE "%,'.$filename.',%" OR value LIKE "%,'.$filename.'")) OR '.
			'(sv.type <> "'.rex_var_media::MEDIALIST.'" AND value LIKE "%'.$filename.'%")'.
			') GROUP BY s.article_id, s.clang';

		$res    = array();
		$usages = array();

		$sql->query($query);
		foreach ($sql as $row) $res[] = $row;

		foreach ($res as $row) {
			$article = sly_Util_Article::findById($row['article_id'], $row['clang']);

			$usages[] = array(
				'title' => $article->getName(),
				'type'  => 'rex-article',
				'id'    => (int) $row['article_id'],
				'clang' => (int) $row['clang'],
				'link'  => 'index.php?page=content&article_id='.$row['article_id'].'&mode=edit&clang='.$row['clang']
			);
		}

		$usages = sly_Core::dispatcher()->filter('SLY_OOMEDIA_IS_IN_USE', $usages, array(
			'filename' => $this->getFileName(),
			'media'    => $this
		));

		return empty($usages) ? false : $usages;
	}

	public function toHTML($attributes = '') {
		$file     = $this->getFullPath();
		$filetype = $this->getExtension();

		switch ($filetype) {
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
				return $this->toImage($attributes);

			case 'js':
				return sprintf('<script type="text/javascript" src="%s"%s></script>', $file, $attributes);

			case 'css':
				return sprintf('<link href="%s" rel="stylesheet" type="text/css"%s>', $file, $attributes);

			default:
				return 'No html-equivalent available for type "'.$filetype.'"';
		}
	}

	public function __toString() {
		return 'OOMedia, "'.$this->getId().'", "'.$this->getFileName().'"'."<br/>\n";
	}

	// new functions by vscope

	public function getExtension() {
		return self::_getExtension($this->name);
	}

	public static function _getExtension($filename) {
		return substr(strrchr($filename, '.'), 1);
	}

	public function getIcon($useDefaultIcon = true) {
		$ext    = $this->getExtension();
		$folder = SLY_HTDOCS_PATH.'sally/backend/assets/';
		$icon   = $folder.'mime-'.$ext.'.png';

		// Dateityp für den kein Icon vorhanden ist

		if (!file_exists($icon)) {
			$icon = $folder.($useDefaultIcon ? 'mime-default.png' : 'mime-error.png');
		}

		return $icon;
	}

	public function save() {
		$sql  = sly_DB_Persistence::getInstance();
		$data = array(
			're_file_id'   => 0,
			'category_id'  => $this->getCategoryId(),
			'filetype'     => $this->getType(),
			'filename'     => $this->getFileName(),
			'originalname' => $this->getOrgFileName(),
			'filesize'     => $this->getSize(),
			'width'        => $this->getWidth(),
			'height'       => $this->getHeight(),
			'title'        => $this->getTitle()
		);

		if ($this->getId() !== null) {
			$data['updatedate'] = time();
			$data['updateuser'] = sly_Util_User::getCurrentUser()->getLogin();

			$sql->update('file', $data, array('id' => $this->getId()));
		}
		else {
			$data['createdate'] = time();
			$data['createuser'] = sly_Util_User::getCurrentUser()->getLogin();

			$sql->insert('file', $data);
		}

		return true;
	}

	public function delete($filename = null) {
		if ($filename != null) {
			$OOMed = OOMedia::getMediaByFileName($filename);
			if ($OOMed) return $OOMed->delete();
		}
		else {
			try {
				$sql = sly_DB_Persistence::getInstance();
				$sql->delete('file', array('id' => $this->getId()));

				if (self::fileExists($this->getFileName())) {
					unlink(SLY_MEDIAFOLDER.DIRECTORY_SEPARATOR.$this->getFileName());
				}

				return true;
			}
			catch (Exception $e) {
				// fallthrough
			}
		}

		return false;
	}

	public static function fileExists($filename) {
		return strlen($filename) > 0 && file_exists(sly_Util_Directory::join(SLY_MEDIAFOLDER, $filename));
	}

	// allowed filetypes

	public static function getDocTypes() {
		static $docTypes = array(
			'bmp', 'css', 'doc', 'docx', 'eps', 'gif', 'gz', 'jpg', 'mov', 'mp3',
			'ogg', 'pdf', 'png', 'ppt', 'pptx','pps', 'ppsx', 'rar', 'rtf', 'swf',
			'tar', 'tif', 'txt', 'wma', 'xls', 'xlsx', 'zip'
		);

		return $docTypes;
	}

	public static function isDocType($type) {
		return in_array($type, self::getDocTypes());
	}

	// allowed image upload types

	public static function getImageTypes() {
		static $imageTypes = array(
			'image/gif',
			'image/jpg',
			'image/jpeg',
			'image/png',
			'image/x-png',
			'image/pjpeg',
			'image/bmp'
		);

		return $imageTypes;
	}

	public static function isImageType($type) {
		return in_array($type, self::getImageTypes());
	}

	public static function compareImageTypes($type1, $type2) {
		static $jpg = array(
			'image/jpg',
			'image/jpeg',
			'image/pjpeg'
		);

		return in_array($type1, $jpg) && in_array($type2, $jpg);
	}

	public function hasValue($value) {
		if ($value[0] == '_') $value = substr($value, 1);
		return isset($this->$value);
	}

	public function getValue($value) {
		if ($value[0] == '_') $value = substr($value, 1);

		// damit alte rex_article felder wie copyright, description
		// noch funktionieren

		if ($this->hasValue($value)) {
			return $this->$value;
		}

		return null;
	}
}
