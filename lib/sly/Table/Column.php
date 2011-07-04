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
	protected $width;
	protected $sortkey;
	protected $direction;
	protected $htmlAttributes;
	protected $content;

	private $table;
	private $idx;

	public function __construct($content, $width = '', $sortkey = '', $htmlAttributes = array()) {
		$this->content        = $content;
		$this->width          = $width;
		$this->sortkey        = $sortkey;
		$this->htmlAttributes = $htmlAttributes;

		if (sly_get('sortby', 'string') == $sortkey) {
			$this->direction = sly_get('direction', 'string') == 'desc' ? 'desc' : 'asc';
		}
		else {
			$this->direction = 'none';
		}
	}

	public function setContent($content) {
		$this->content = $content;
	}

	public function setTable(sly_Table $table) {
		$this->table = $table;
	}

	public function setIndex($idx) {
		$this->idx = (int) $idx;
	}

	public function render() {
		if (!empty($this->width)) {
			$this->htmlAttributes['style'] = 'width:'.$this->width;
		}

		return $this->renderView('column.phtml', array('table' => $this->table, 'index' => $this->idx));
	}

	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/table/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exception('View '.$file.' could not be found.');
	}
}
