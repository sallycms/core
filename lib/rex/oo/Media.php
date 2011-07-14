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
	private static $dummeFileSize = null;

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

	// new functions by vscope

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
}
