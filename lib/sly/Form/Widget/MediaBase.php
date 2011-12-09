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
 * Media widget
 *
 * This element will render a special widget that allows the user to select
 * a file from the mediapool. The handled value is the file's name, not its ID.
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Widget_MediaBase extends sly_Form_ElementBase {
	protected $filetypes  = array();
	protected $categories = array();

	public function filterByCategory($catID, $recursive = false) {
		$catID = (int) $catID;

		if (!$recursive) {
			if (!in_array($catID, $this->categories)) {
				$this->categories[] = $catID;
			}
		}
		else {
			$serv = sly_Service_Factory::getMediaCategoryService();
			$tree = $serv->findTree($catID, false);

			foreach ($tree as $id) {
				$this->categories[] = $id;
			}

			$this->categories = array_unique($this->categories);
		}

		return $this->categories;
	}

	public function filterByFiletypes(array $types) {
		foreach ($types as $type) {
			$this->filetypes[] = sly_Util_Mime::getType('tmp.'.ltrim($type, '.'));
		}

		$this->filetypes = array_unique($this->filetypes);
		return $this->filetypes;
	}

	public function clearCategoryFilter() {
		$this->categories = array();
	}

	public function clearFiletypeFilter() {
		$this->filetypes = array();
	}
}
