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
 * @ingroup table
 */
class sly_Table_Column extends sly_Viewable {
	protected $width;          ///< string
	protected $sortkey;        ///< string
	protected $direction;      ///< string
	protected $htmlAttributes; ///< array
	protected $content;        ///< string

	private $table; ///< sly_Table
	private $idx;   ///< int

	/**
	 * @param string $content
	 * @param string $width
	 * @param string $sortkey
	 * @param array  $htmlAttributes
	 */
	public function __construct($content, $width = '', $sortkey = '', $htmlAttributes = array()) {
		$this->content        = $content;
		$this->width          = $width;
		$this->sortkey        = $sortkey;
		$this->htmlAttributes = $htmlAttributes;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @param sly_Table $table
	 */
	public function setTable(sly_Table $table) {
		$this->table = $table;
	}

	/**
	 * @param int $idx
	 */
	public function setIndex($idx) {
		$this->idx = (int) $idx;
	}

	/**
	 * @return string
	 */
	public function render() {
		if (!empty($this->width)) {
			$this->htmlAttributes['style'] = 'width:'.$this->width;
		}

		$id = $this->table->getID();

		if (sly_get($id.'_sortby', 'string') === $this->sortkey) {
			$this->direction = sly_get($id.'_direction', 'string') == 'desc' ? 'desc' : 'asc';
		}
		else {
			$this->direction = 'none';
		}

		return $this->renderView('column.phtml', array('table' => $this->table, 'index' => $this->idx));
	}

	/**
	 * @throws sly_Exception
	 * @param  string $file
	 * @return string
	 */
	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/table/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exception(t('view_not_found', $file));
	}
}
