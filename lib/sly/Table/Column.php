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
	 * @param  string $content
	 * @param  mixed  $classes     CSS classes as either a string or an array
	 * @param  string $sortkey
	 * @param  array  $attributes
	 * @param  string $width
	 * @return sly_Table_Column
	 */
	public static function factory($content, $classes = '', $sortkey = '', array $attributes = null, $width = '') {
		$attributes = (array) $attributes;

		if (!empty($classes)) {
			$classes = is_string($classes) ? trim($classes) : implode(' ', array_filter(array_unique($classes)));
			$attributes['class'] = $classes;
		}

		return new self($content, $width, $sortkey, $attributes);
	}

	/**
	 * @param  string $uri       URI to the icon
	 * @param  string $link      optional URI to link to
	 * @param  mixed  $classes   CSS classes as either a string or an array
	 * @param  string $sortkey
	 * @return sly_Table_Column
	 */
	public static function icon($uri, $link = null, $title = null, $classes = '', $sortkey = '') {
		$attributes = array('class' => 'sly-icon');
		$icon       = sprintf('<img src="" alt="" title="%s" />', $uri, sly_html($title));

		if ($link) {
			$icon = sprintf('<a href="%s">%s</a>', sly_html($link), $icon);
		}

		if (!empty($classes)) {
			$classes = is_string($classes) ? trim($classes) : implode(' ', array_filter(array_unique($classes)));
			$attributes['class'] .= ' '.$classes;
		}

		return new self($icon, '', $sortkey, $attributes);
	}

	/**
	 * @param  string $uri       URI to the icon
	 * @param  string $link      optional URI to link to
	 * @param  mixed  $classes   CSS classes as either a string or an array
	 * @param  string $sortkey
	 * @return sly_Table_Column
	 */
	public static function sprite($spriteClass, $link = null, $title = null, $classes = '', $sortkey = '') {
		$attributes = array('class' => 'sly-icon');
		$sprite     = sly_Util_HTML::getSpriteLink($link, $title, $spriteClass);

		if (!empty($classes)) {
			$classes = is_string($classes) ? trim($classes) : implode(' ', array_filter(array_unique($classes)));
			$attributes['class'] .= ' '.$classes;
		}

		return new self($sprite, '', $sortkey, $attributes);
	}

	/**
	 * @param  string $name
	 * @param  string $value
	 * @return sly_Table_Column  self
	 */
	public function setAttribute($name, $value) {
		$this->htmlAttributes[$name] = trim($value);
		return $this;
	}

	/**
	 * @param  string $name
	 * @param  string $value
	 * @return string         found attribute value or null
	 */
	public function getAttribute($name) {
		return sly_setarraytype($this->htmlAttributes, $name, 'string', null);
	}

	/**
	 * @param  string $content
	 * @return sly_Table_Column  self
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * @param  sly_Table $table
	 * @return sly_Table_Column  self
	 */
	public function setTable(sly_Table $table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * @param  int $idx
	 * @return sly_Table_Column  self
	 */
	public function setIndex($idx) {
		$this->idx = (int) $idx;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getColspan() {
		return sly_setarraytype($this->htmlAttributes, 'colspan', 'int', 1);
	}

	/**
	 * @param  sly_Request $request  the request to use or null for the global one
	 * @return string
	 */
	public function render(sly_Request $request = null) {
		if (!empty($this->width)) {
			$this->htmlAttributes['style'] = 'width:'.$this->width;
		}

		$request = $request ? $request : sly_Core::getRequest();
		$id      = $this->table->getID();

		if ($request->get($id.'_sortby', 'string') === $this->sortkey) {
			$this->direction = $request->get($id.'_direction', 'string') === 'desc' ? 'desc' : 'asc';
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
