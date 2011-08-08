<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 *
 * The OOArticleSlice class is an object wrapper over the database table rex_article_slice.
 * Together with OOArticle and OOCategory it provides an object oriented
 * Framework for accessing vital parts of your website.
 * This framework can be used in Modules, Templates and PHP-Slices!
 *
 * @ingroup redaxo2
 */
class OOArticleSlice {
	private $_id;
	private $_article_id;
	private $_clang;
	private $_slot;
	private $_slice_id;
	private $_prior;

	private $_createdate;
	private $_updatedate;
	private $_createuser;
	private $_updateuser;
	private $_revision;

	const CACHE_NS = 'sly.slice';

	/**
	 * Constructor
	 */
	public function __construct($id, $article_id, $clang, $slot, $prior,
		$createdate, $updatedate, $createuser, $updateuser, $revision, $slice_id = 0) {
		$this->_id         = (int) $id;
		$this->_article_id = (int) $article_id;
		$this->_clang      = (int) $clang;
		$this->_slot       = $slot;
		$this->_slice_id   = (int) $slice_id;
		$this->_prior   = (int) $prior;

		$this->_createdate = (int) $createdate;
		$this->_updatedate = (int) $updatedate;
		$this->_createuser = $createuser;
		$this->_updateuser = $updateuser;
		$this->_revision   = (int) $revision;
	}

	/**
	 * @return OOArticleSlice
	 */
	public static function getArticleSliceById($id, $revision = 0) {

		$namespace = 'sly.slice';
		$id        = (int) $id;
		$revision  = (int) $revision;
		$key       = sly_Cache::generateKey('by_id', $id, $revision);
		$obj       = sly_Core::cache()->get($namespace, $key, null);

		if ($obj === null) {
			$obj = self::_getSliceWhere('id = '.$id.' AND revision = '.$revision);
			sly_Core::cache()->set(self::CACHE_NS, $key, $obj);
		}

		return $obj;
	}

	public static function getSliceIdsForSlot($article_id, $clang, $slot = null) {
		$cache    = sly_Cache::factory();
		$cachekey = sly_Cache::generateKey('slice_ids_for_slot', $article_id, $clang, $slot);
		$ids      = $cache->get(self::CACHE_NS, $cachekey);
		if(is_null($ids)) {
			$ids = array();
			$sql = sly_DB_Persistence::getInstance();
			$where = array('article_id' => $article_id, 'clang' => $clang);
			if(!is_null($slot)) {
				$where['slot'] = $slot;
			}
			$sql->select('article_slice', 'id', $where, null, 'slot, prior ASC');
			foreach($sql as $row){
				$ids[] = $row['id'];
			}
			$cache->set(self::CACHE_NS, $cachekey, $ids);

			sly_Core::dispatcher()->notify('CLANG_ARTICLE_GENERATED', '', array(
				'id'      => $article_id,
				'clang'   => $clang
			));
		}
		return $ids;
	}

	/**
	 * Return the first slice for an article.
	 *
	 * This can then be used to iterate over all the
	 * slices in the order as they appear using the
	 * getNextSlice() function.
	 * Returns an OOArticleSlice object
	 *
	 * @param  int     $articleID  The article id
	 * @param  string  $slot       The slot (if null, the first defined slot will be used)
	 * @param  int     $clang      The desired content language (if null, the current language will be used)
	 * @return OOArticleSlice
	 */
	public static function getFirstSliceForArticle($articleID, $slot = null, $clang = null, $revision = 0) {
		if ($clang === null) $clang = sly_Core::getCurrentClang();

		if ($slot === null) {
			$template = sly_Util_Article::findById($articleID, $clang)->getTemplateName();
			$slot     = sly_Service_Factory::getTemplateService()->getFirstSlot($template);

			if ($slot === null) return null;
		}

		$prefix    = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$articleID = (int) $articleID;
		$clang     = (int) $clang;
		$revision  = (int) $revision;

		return self::_getSliceWhere(
			'a.article_id = '.$articleID.' AND a.clang = '.$clang.' AND a.slot = '. sly_DB_PDO_Persistence::getInstance()->quote($slot) .' AND '.
			'((a.prior = 0  AND a.id = b.id) '.
			'OR (b.slot != a.slot AND b.id = a.prior)) '.
			'AND a.revision = '.$revision.' AND b.revision = '.$revision,
			$prefix.'article_slice a, '.$prefix.'article_slice b',
			'a.*'
		);
	}

	/*
	 * CLASS Function:
	 * Return all slices for an article that have a certain
	 * module type.
	 * Returns an array of OOArticleSlice objects
	 */
	public static function getSlicesForArticleOfType($articleID, $module, $clang = false, $revision = 0) {
		if ($clang === false) $clang = sly_Core::getCurrentClang();

		$prefix    = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$articleID = (int) $articleID;
		$clang     = (int) $clang;
		$revision  = (int) $revision;
		$module    = mysql_real_escape_string($module);

		return self::_getSliceWhere(
			'article_id = '.$articleID.' AND clang = '.$clang.' AND module = "'.$module.'" AND revision = '.$revision,
			array()
		);
	}

	/**
	 * Return the next slice for this article
	 * Returns an OOArticleSlice object.
	 *
	 * @return OOArticleSlice
	 */
	public function getNextSlice() {
		return self::_getSliceWhere('prior > '.$this->_prior.' AND slot = "'.mysql_real_escape_string($this->_slot).'" AND clang = '.$this->_clang.' AND article_id = '.$this->_article_id.' AND clang = '.$this->_clang.' ORDER BY prior ASC LIMIT 1');
	}

	/**
	 * @return OOArticleSlice
	 */
	public function getPreviousSlice() {
		return self::_getSliceWhere('prior < '.$this->_prior.' AND slot = "'.mysql_real_escape_string($this->_slot).'" AND clang = '.$this->_clang.' AND article_id = '.$this->_article_id.' AND clang = '.$this->_clang.' ORDER BY prior DESC LIMIT 1');
	}

	/**
	 * @return string the content of the slice
	 */
	public function getOutput() {
		$cachedir = SLY_DYNFOLDER.'/internal/sally/article_slice/';
		sly_Util_Directory::create($cachedir);
		$modulefile = sly_Service_Factory::getModuleService()->getOutputFilename($this->getModule());

		$slice_content_file = $cachedir.$this->getSliceId().'-'.md5($modulefile).'.slice.php';

		if (!file_exists($slice_content_file)) {
			$slice = $this->getSlice();
			$slice_content = $slice->getOutput();
			$slice_content = self::replaceLinks($slice_content);
			$slice_content = $this->replaceGlobals($slice_content);

			if (!file_put_contents($slice_content_file, $slice_content)) {
				return t('slice_could_not_be_generated').' '.t('check_rights_in_directory').SLY_DYNFOLDER.'/internal/sally/articles/';
			}
		}

		if (file_exists($slice_content_file)) {
			ob_start();
			$this->includeContentFile($slice_content_file);
			$content = ob_get_clean();
		}
		
		return $content;
	}

	public function getInput() {
		$slice = $this->getSlice();
		$content = $slice->getInput();
		$content = $this->replaceGlobals($content);
		return $content;
	}

	/**
	 * @deprecated
	 */
	public function printContent() {
		print $this->getOutput();
	}
	
	private function includeContentFile($slice_content_file) {
		if (file_exists($slice_content_file)) {
			$article = $this->getArticle();
			include $slice_content_file;
		}
	}

	/**
	 * @param  string $where
	 * @param  string $table
	 * @param  string $fields
	 * @param  mixed  $default
	 * @return array  OOArticleSlice
	 */
	public static function _getSliceWhere($where, $table = null, $fields = null, $default = null) {
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		if (!$table)  $table  = $prefix.'article_slice';
		if (!$fields) $fields = '*';

		$sql = sly_DB_Persistence::getInstance();
		$sql->query('SELECT '.$fields.' FROM '.$table.' WHERE '.$where);

		foreach ($sql as $row) {
			$slices[] = new OOArticleSlice(
				$row['id'], $row['article_id'], $row['clang'], $row['slot'],
				$row['prior'], $row['createdate'],
				$row['updatedate'], $row['createuser'], $row['updateuser'], $row['revision'],
				$row['slice_id']
			);
		}

		if (!empty($slices)) return count($slices) == 1 ? $slices[0] : $slices;
		return $default;
	}

	public function getArticle() {
		return sly_Util_Article::findById($this->getArticleId());
	}

	public function getArticleId()  { return $this->_article_id;             }
	public function getClang()      { return $this->_clang;                  }
	public function getSlot()       { return $this->_slot;                   }
	public function getRevision()   { return $this->_revision;               }
	/**
	 * @deprecated
	 * @return string
	 */
	public function getModule()     { return $this->getSlice()->getModule(); }
	public function getId()         { return $this->_id;                     }
	public function getPrior()      { return $this->_prior;                  }
	public function getSliceId()    { return $this->_slice_id;               }
	public function getCreatedate() { return $this->_createdate;             }
	public function getUpdatedate() { return $this->_updatedate;             }
	public function getCreateuser() { return $this->_createuser;             }
	public function getUpdateuser() { return $this->_updateuser;             }

	/**
	 *
	 * @return Sly_Model_Slice
	 */
	public function getSlice() {
		return sly_Service_Factory::getSliceService()->findById($this->getSliceId());
	}

	public function getValue($index)     { return $this->getRexVarValue('SLY_VALUE', $index);     }
	public function getLink($index)      { return $this->getRexVarValue('SLY_LINK', $index);      }
	public function getLinkList($index)  { return $this->getRexVarValue('SLY_LINKLIST', $index);  }
	public function getMedia($index)     { return $this->getRexVarValue('SLY_MEDIA', $index);     }
	public function getMediaList($index) { return $this->getRexVarValue('SLY_MEDIALIST', $index); }

	public function getLinkUrl($index) {
		$article = sly_Util_Article::findById($this->getLink($index));
		return $article ? $article->getUrl() : '';
	}

	public function getMediaUrl($index) {
		return SLY_MEDIAFOLDER.'/'.$this->getMedia($index);
	}

	private function replaceGlobals($content) {
		// Slice-abhängige globale Variablen ersetzen
		static $search = array(
			'ARTICLE_ID',
			'CLANG_ID',
			'TEMPLATE_NAME',
			'MODULE',
			'SLICE_ID',
			'SLOT',
			'POSITION',
			'CREATE_USER_LOGIN'
		);

		$replace = array(
			$this->getArticleId(),
			$this->getClang(),
			$this->getArticle()->getTemplateName(),
			$this->getModule(),
			$this->getId(),
			$this->getSlot(),
			$this->getPrior(),
			$this->getCreateuser()
		);

		return str_replace($search, $replace, $content);
	}

	private static function replaceLinks($content) {
		// Hier beachten, dass man auch ein Zeichen nach dem jeweiligen Link mitmatched,
		// damit beim ersetzen von z.b. redaxo://11 nicht auch innerhalb von redaxo://112
		// ersetzt wird
		// siehe dazu: http://forum.redaxo.de/ftopic7563.html

		// -- preg match redaxo://[ARTICLEID]-[CLANG] --
		preg_match_all('@(?:redaxo|sally)://([0-9]*)\-([0-9]*)(.){1}/?@im', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], $match[2]);
			$content = str_replace($match[0], $replace, $content);
		}

		// -- preg match redaxo://[ARTICLEID] --

		preg_match_all('@(?:redaxo|sally)://([0-9]*)(.){1}/?@im', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], false);
			$content = str_replace($match[0], $replace, $content);
		}

		return $content;
	}

	private static function getReplacementLink($articleID, $clang) {
		$art = sly_Util_Article::findById($articleID, $clang);
		return $art ? $art->getUrl('', '', true) : '';
	}

	protected function getRexVarValue($type, $key) {
		$value = sly_Service_Factory::getSliceValueService()->findBySliceTypeFinder($this->getSliceId(), $type, $key);
		return $value ? $value->getValue() : null;
	}
}
