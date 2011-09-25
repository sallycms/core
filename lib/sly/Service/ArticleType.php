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
 * Service-Klasse for Article types
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleType {
	private $data; ///< array

	public function __construct() {
		$this->data = (array) sly_Core::config()->get('ARTICLE_TYPES');
	}

	/**
	 * @return array
	 */
	public function getArticleTypes() {
		$types = array();
		foreach (array_keys($this->data) as $name) {
			$types[$name] = $this->getTitle($name);
		}
		return $types;
	}

	/**
	 * @param  string $articleType
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($articleType, $property, $default = '') {
		$this->exists($articleType, true);
		return isset($this->data[$articleType][$property]) ? $this->data[$articleType][$property] : $default;
	}

	/**
	 * @param  string $articleType
	 * @return string
	 */
	public function getTitle($articleType) {
		$title = $this->get($articleType, 'title');
		return empty($title) ? $articleType : $title;
	}

	/**
	 * @param  string $articleType
	 * @return string
	 */
	public function getTemplate($articleType) {
		return $this->get($articleType, 'template');
	}

	/**
	 * @throws sly_Exception
	 * @param  string  $articleType
	 * @param  boolean $throwException
	 * @return boolean
	 */
	public function exists($articleType, $throwException = false) {
		if (!array_key_exists($articleType, $this->data)) {
			if ($throwException) {
				throw new sly_Exception(t('exception_articletype_not_exists', $articleType));
			}

			return false;
		}

		return true;
	}
}
