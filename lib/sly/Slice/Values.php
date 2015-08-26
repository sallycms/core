<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @since  0.6
 * @author zozi@webvariants.de
 * @author christoph@webvariants.de
 */
class sly_Slice_Values {
	private $data; ///< array

	/**
	 * Constructor
	 *
	 * @param array $data  the slice data
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * Get a single slice value
	 *
	 * @param  string $id       the value's id (form element's name)
	 * @param  string $default  value to use if the $id was not found
	 * @return mixed            the value or the default
	 */
	public function get($id, $default = null) {
		if (!array_key_exists($id, $this->data)) return $default;
		return $this->data[$id];
	}

	/**
	 * Get a list of values
	 *
	 * @param  string  $id
	 * @param  string  $type        variable type like 'int' or 'string'
	 * @param  boolean $unique      whether or not to remove duplicates
	 * @param  boolean $separators  string of separator characters
	 * @return array                [<value>, <value>, ...]
	 */
	public function getMany($id, $type, $unique = false, $separators = ',') {
		$value = $this->get($id, '');
		if (mb_strlen($value) === 0) return array();

		$elements = preg_split('/['.preg_quote($separators, '/').']/', $value, null, PREG_SPLIT_NO_EMPTY);

		foreach ($elements as $idx => $element) {
			$elements[$idx] = sly_settype($element, $type);
		}

		if ($unique) {
			$elements = array_values(array_unique($elements));
		}

		return $elements;
	}

	/**
	 * Get a list of articles
	 *
	 * @param  string  $id
	 * @param  boolean $unique      whether or not to remove duplicates
	 * @param  boolean $separators  string of separator characters
	 * @return array                [sly_Model_Article, sly_Model_Article, ...]
	 */
	public function getArticles($id, $unique = false, $separators = ',') {
		$ids = $this->getMany($id, 'int', $unique, $separators);
		$res = array();

		foreach ($ids as $id) {
			$article = sly_Util_Article::findById($id);
			if ($article) $res[] = $article;
		}

		return $res;
	}

	/**
	 * Get a list of categories
	 *
	 * @param  string  $id
	 * @param  boolean $unique      whether or not to remove duplicates
	 * @param  boolean $separators  string of separator characters
	 * @return array                [sly_Model_Category, sly_Model_Category, ...]
	 */
	public function getCategories($id, $unique = false, $separators = ',') {
		$ids = $this->getMany($id, 'int', $unique, $separators);
		$res = array();

		foreach ($ids as $id) {
			$category = sly_Util_Category::findById($id);
			if ($category) $res[] = $category;
		}

		return $res;
	}

	/**
	 * Get a list of filenames
	 *
	 * @param  string  $id
	 * @param  boolean $skipMissing
	 * @param  boolean $unique       whether or not to remove duplicates
	 * @param  boolean $separators   string of separator characters
	 * @return array                 [test.jpg, dummy.png, ...]
	 */
	public function getFilenames($id, $skipMissing = true, $unique = false, $separators = ',') {
		$filenames = $this->getMany($id, 'string', $unique, $separators);
		$res       = array();

		if (!$skipMissing) {
			return $filenames;
		}

		foreach ($filenames as $name) {
			if (sly_Util_Medium::findByFilename($name) instanceof sly_Model_Medium) {
				$res[] = $name;
			}
		}

		return $res;
	}

	/**
	 * Get a list of sly_Model_Medium instances
	 *
	 * @param  string  $id
	 * @param  boolean $skipMissing
	 * @param  boolean $unique       whether or not to remove duplicates
	 * @param  boolean $separators   string of separator characters
	 * @return array                 [sly_Model_Medium, sly_Model_Medium, ...]
	 */
	public function getMedia($id, $skipMissing = true, $unique = false, $separators = ',') {
		$filenames = $this->getFilenames($id, $skipMissing, $unique, $separators);
		$res       = array();

		foreach ($filenames as $name) {
			$medium = sly_Util_Medium::findByFilename($name);
			if ($medium) $res[] = $medium;
		}

		return $res;
	}

	/**
	 * Get an article instance
	 *
	 * @param  string  $id
	 * @param  mixed   $default
	 * @return sly_Model_Article
	 */
	public function getArticle($id, $default = null) {
		return sly_Util_Article::findById($this->get($id, null), $default);
	}

	/**
	 * Get a category instance
	 *
	 * @param  string  $id
	 * @param  mixed   $default
	 * @return sly_Model_Category
	 */
	public function getCategory($id, $default = null) {
		return sly_Util_Category::findById($this->get($id, null), $default);
	}

	/**
	 * Get a medium instance
	 *
	 * @param  string  $id
	 * @param  mixed   $default
	 * @return sly_Model_Medium
	 */
	public function getMedium($id, $default = null) {
		return sly_Util_Medium::findByFilename($this->get($id, null), $default);
	}

	/**
	 * Get a URL to an article
	 *
	 * @param  string  $id
	 * @param  array   $params
	 * @param  string  $divider
	 * @param  boolean $absolute
	 * @param  boolean $secure
	 * @return string
	 */
	public function getUrl($id, $params = array(), $divider = '&amp;', $absolute = false, $secure = null) {
		try {
			return sly_Util_Article::getUrl($this->get($id, sly_Util_Article::CURRENT_ARTICLE), null, $params, $divider, $absolute, $secure);
		}
		catch (Exception $e) {
			return '#';
		}
	}

	/**
	 * Get a URL to an article
	 *
	 * @param  string  $id
	 * @param  array   $attributes
	 * @param  boolean $forceUri
	 * @return string
	 */
	public function getImageTag($id, array $attributes = array(), $forceUri = false) {
		return sly_Util_HTML::getImageTag($this->get($id, ''), $attributes, $forceUri);
	}

	/**
	 * @throws sly_Exception
	 * @param  string $method
	 * @param  array  $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		$event      = strtoupper('SLY_SLICEVALUES_'.$method);
		$dispatcher = sly_Core::dispatcher();

		if (!$dispatcher->hasListeners($event)) {
			throw new sly_Exception('Call to undefined method '.get_class($this).'::'.$method.'()');
		}

		return $dispatcher->filter($event, $this, array('method' => $method, 'arguments' => $arguments, 'object' => $this));
	}
}
