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
 * Link widget
 *
 * This element will render a special widget that allows the user to select
 * one article. The article will be returned without any language information,
 * so only its ID is returned.
 * Selection will be performed in the so-called 'linkmap', a special popup for
 * browsing through the article structure.
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Widget_LinkBase extends sly_Form_ElementBase {
	protected $types      = array();
	protected $categories = array();

	/**
	 * @return sly_Form_Widget_LinkBase  the widget itself
	 */
	public function filterByCategories(array $cats, $recursive = false) {
		foreach ($cats as $cat) $this->filterByCategory($cat, $recursive);
		return $this;
	}

	/**
	 * @return sly_Form_Widget_LinkBase  the widget itself
	 */
	public function filterByCategory($cat, $recursive = false) {
		$catID = $cat instanceof sly_Model_Category ? $cat->getId() : (int) $cat;

		if (!$recursive) {
			if (!in_array($catID, $this->categories)) {
				$this->categories[] = $catID;
			}
		}
		else {
			$serv = sly_Service_Factory::getCategoryService();
			$tree = $serv->findTree($catID);

			foreach ($tree as $cat) {
				$this->categories[] = $cat->getId();
			}

			$this->categories = array_unique($this->categories);
		}

		return $this;
	}

	/**
	 * @return sly_Form_Widget_LinkBase  the widget itself
	 */
	public function filterByArticleTypes(array $types) {
		foreach ($types as $type) $this->types[] = $type;
		$this->types = array_unique($this->types);

		return $this;
	}

	/**
	 * @return sly_Form_Widget_LinkBase  the widget itself
	 */
	public function clearCategoryFilter() {
		$this->categories = array();
		return $this;
	}

	/**
	 * @return sly_Form_Widget_LinkBase  the widget itself
	 */
	public function clearArticleTypeFilter() {
		$this->types = array();
		return $this;
	}
}
