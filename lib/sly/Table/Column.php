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
class sly_Table_Column {
	protected $width;
	protected $sortkey;
	protected $direction;
	protected $htmlAttributes;
	protected $content;

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

	public function render(sly_Table $table, $index) {
		if (!empty($this->width)) {
			$this->htmlAttributes['style'] = 'width:'.$this->width;
		}

		include SLY_INCLUDE_PATH.'/views/_table/table/column.phtml';
	}
}
