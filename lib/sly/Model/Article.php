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
 * Business Model Klasse für Artikel
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Article extends sly_Model_Base_Article {

	public function isStartArticle() {
		return $this->getStartpage() == 1;
	}

	/**
	 * returns the category id
	 * @return int
	 */
	public function getCategoryId() {
		return $this->isStartArticle() ? $this->getId() : $this->getParentId();
	}

	/**
	 * @return sly_Model_Category
	 */
	public function getCategory() {
		return sly_Service_Factory::getCategoryService()->findById($this->getCategoryId(), $this->getClang());
	}

	/**
	 * returns true if the articletype is set
	 *
	 * @return boolean
	 */
	public function hasType() {
		return !empty($this->type);
	}
	
	public function hasTemplate() {
		if($this->hasType()) {
			$templateName = $this->getTemplateName(); 
			$templateService = sly_Service_Factory::getTemplateService();
			return !empty($templateName) && $templateService->exists($templateName);
		}
		return false; 
	}

	/**
	 * returns the template name of the template associated with the articletype of this article
	 *
	 * @return string the template name
	 */
	public function getTemplateName() {
		return sly_Service_Factory::getArticleTypeService()->getTemplate($this->type);
	}

	/**
	 * prints the articlecontent for a given slot, or if empty for all slots
	 *
	 * @param string $slot
	 */
	public function printContent($slot = null) {
		$ids = OOArticleSlice::getSliceIdsForSlot($this->getId(), $this->getClang(), $slot);
		foreach ($ids as $id) {
			OOArticleSlice::getArticleSliceById($id)->printContent();
		}
	}

	/**
	 * returns the articlecontent for a given slot, or if empty for all slots
	 *
	 * @deprecated use getContent() instead
	 * @param string $slot
	 * @return string
	 */
	public function getArticle($slot = null) {
		return $this->getContent($slot);
	}

	/**
	 * returns the articlecontent for a given slot, or if empty for all slots
	 *
	 * @param string $slot
	 * @return string
	 */
	public function getContent($slot = null) {
		ob_start();
		$this->printContent($slot);
		return ob_get_clean();
	}

	/**
	 * returns the rendered template with the articlecontent
	 *
	 * @return string
	 */
	public function getArticleTemplate() {
		$tplserv = sly_Service_Factory::getTemplateService();

		if ($this->hasType() && $tplserv->exists($this->getTemplateName())) {
			$params['article'] = $this;
			ob_start();
			ob_implicit_flush(0);
			$tplserv->includeFile($this->getTemplateName(), $params);
			$content = ob_get_clean();
		}
		else {
			$content = 'No article type or template given.';
		}

		return $content;
	}
}
