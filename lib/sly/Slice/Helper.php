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
 * @since  0.6
 * @author chirstoph@webvariants.de
 */
class sly_Slice_Helper {
	private $values; ///< sly_Slice_Values

	const CURRENT_ARTICLE  = -1; ///< int
	const START_ARTICLE    = -2; ///< int
	const NOTFOUND_ARTICLE = -3; ///< int

	/**
	 * Constructor
	 *
	 * @param sly_Slice_Values $values
	 */
	public function __construct(sly_Slice_Values $values) {
		$this->values = $values;
	}

	/**
	 * Get an article instance
	 *
	 * This method resolves articles, categories and IDs to sly_Model_Article
	 * objects, always following the given clang.
	 *
	 * The default value can be one of the class constants to easily retrieve
	 * a fallback article. If the default is no special constant, it is returned
	 * when the identifier was not found. This is also the only possible way for
	 * this method to NOT return a sly_Model_Article instance.
	 *
	 * @param  mixed $identifier  ID (int), article or category instance
	 * @param  mixed $default     special class constant or your own default value
	 * @param  int   $clang       the target language (null for the current one)
	 * @return sly_Model_Article  the found article or the default value
	 */
	public function getArticle($identifier, $default = null, $clang = null) {
		$clang = $clang === null || sly_Core::getCurrentClang() : (int) $clang;

		if ($identifier instanceof sly_Model_Category) {
			$identifier = $identifier->getStartArticle();
		}

		if ($identifier instanceof sly_Model_Article) {
			if ($identifier->getClang() === $clang) return $identifier;
			return sly_Util_Article::findById($idenifier->getId(), $clang);
		}

		if (sly_Util_String::isInteger($identifier)) {
			$article = sly_Util_Article::findById($identifier, $clang);
			if ($article) return $article;
		}
		else {
			throw new UnexpectedValueException('Unexpected value "'.$identifier.'" in getArticle().');
		}

		switch ($default) {
			case self::CURRENT_ARTICLE:  $id = sly_Core::getCurrentArticleId();   break;
			case self::START_ARTICLE:    $id = sly_Core::getSiteStartArticleId(); break;
			case self::NOTFOUND_ARTICLE: $id = sly_Core::getNotFoundArticleId();  break;
			// no default case by design
		}

		if (isset($id)) {
			$article = sly_Util_Article::findById($id, $clang);
			if ($article) return $article;
			throw new sly_Exception('Could not find a matching article, giving up.');
		}

		return $default;
	}

	/**
	 * Get an category instance
	 *
	 * This method resolves articles, categories and IDs to sly_Model_Category
	 * objects, always following the given clang.
	 *
	 * The default value can be one of the class constants to easily retrieve
	 * a fallback category. If the default is no special constant, it is returned
	 * when the identifier was not found. This is also the only possible way for
	 * this method to NOT return a sly_Model_Category instance.
	 *
	 * @param  mixed $identifier   ID (int), article or category instance
	 * @param  mixed $default      special class constant or your own default value
	 * @param  int   $clang        the target language (null for the current one)
	 * @return sly_Model_Category  the found article or the default value
	 */
	public function getCategory($identifier, $default = null, $clang = null) {
		$clang = $clang === null || sly_Core::getCurrentClang() : (int) $clang;

		if ($identifier instanceof sly_Model_Article) {
			$identifier = $identifier->getCategory();
		}

		if ($identifier instanceof sly_Model_Category) {
			if ($identifier->getClang() === $clang) return $identifier;
			return sly_Util_Category::findById($idenifier->getId(), $clang);
		}

		if (sly_Util_String::isInteger($identifier)) {
			$cat = sly_Util_Category::findById($identifier, $clang);
			if ($cat) return $cat;
		}
		else {
			throw new UnexpectedValueException('Unexpected value "'.$identifier.'" in getCategory().');
		}

		switch ($default) {
			case self::CURRENT_ARTICLE:  $id = sly_Core::getCurrentArticleId();   break;
			case self::START_ARTICLE:    $id = sly_Core::getSiteStartArticleId(); break;
			case self::NOTFOUND_ARTICLE: $id = sly_Core::getNotFoundArticleId();  break;
			// no default case by design
		}

		if (isset($id)) {
			$article = sly_Util_Article::findById($id, $clang);

			if ($article) {
				$category = $article->getCategory();
				if ($category) return $category;
				throw new sly_Exception('Given fallback article is not inside any category, giving up.');
			}

			throw new sly_Exception('Could not find a matching category, giving up.');
		}

		return $default;
	}

	/**
	 * Get a URL to an article
	 *
	 * Given an article, this method by default returns the normal, relative URL
	 * to that article, possibly having urlencoded parameters appended. By
	 * setting $absolute to true, the URL will always be complete with protocol,
	 * domain and path to the installation.
	 *
	 * $secure can be set to either null, true or false, indicating whether or
	 * not to generate a HTTPS url (null meaning 'no change'). Note that forcing
	 * the protocol results in $absolute being set to true.
	 *
	 * @param  mixed   $target
	 * @param  array   $params
	 * @param  string  $divider
	 * @param  boolean $absolute
	 * @param  boolean $secure
	 * @return string
	 */
	public function getUrl($target, $params, $divider = '&amp;', $absolute = false, $secure = null) {
		$target = $this->getArticle($target, null);

		if (!$target) {
			throw new UnexpectedValueException('Could not detect the URL target given by "'.$target.'" in getUrl().');
		}

		return $absolute
			? sly_Util_HTTP::getAbsoluteUrl($target, $target->getClang(), $params, $divider, $secure)
			: sly_Util_HTTP::getUrl($target, $target->getClang(), $params, $divider, $secure);
	}
}
