<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	/**
	 * @return boolean
	 */
	public function isStartArticle() {
		return $this->getStartpage() == 1;
	}

	/**
	 * returns the category id
	 *
	 * @return int
	 */
	public function getCategoryId() {
		return $this->isStartArticle() ? $this->getId() : $this->getParentId();
	}

	/**
	 * @return sly_Model_Category
	 */
	public function getCategory() {
		$catService = sly_Core::getContainer()->getCategoryService();

		return $catService->findByPK($this->getCategoryId(), $this->getClang());
	}

	/**
	 * returns true if the articletype is set
	 *
	 * @return boolean
	 */
	public function hasType() {
		return !empty($this->type);
	}

	/**
	 * @param  sly_Service_Template $tplService
	 * @return boolean
	 */
	public function hasTemplate() {
		if ($this->hasType()) {
			$templateName    = $this->getTemplateName();
			$tplService      = sly_Core::getContainer()->getTemplateService();

			return !empty($templateName) && $tplService->exists($templateName);
		}

		return false;
	}

	/**
	 * returns the template name of the template associated with the articletype of this article
	 *
	 * @return string  the template name
	 */
	public function getTemplateName() {
		$atService = sly_Core::getContainer()->getArticleTypeService();

		return $atService->getTemplate($this->type);
	}

	/**
	 * returns the article content for a given slot, or if empty for all slots
	 *
	 * @param  string        $slot
	 * @param  sly_Container $container
	 * @return string
	 */
	public function getContent($slot = null, sly_Container $container = null) {
		$content       = '';
		$container     = $container ?: sly_Core::getContainer();
		$moduleService = $container->getModuleService();
		$typeService   = $container->getArticleTypeService();

		foreach ($this->getSlices($slot) as $slice) {
			$module = $slice->getModule();

			if (!$moduleService->exists($module)) {
				trigger_error('Module '.$module.' does not exists in article/clang '.$this->getId().'/'.$this->getClang(), E_USER_WARNING);
				continue;
			}

			if (!$typeService->hasModule($this->getType(), $module, $slice->getSlot())) {
				trigger_error(
					'Module '.$module.' is not allowed in type/slot '.$this->getType().'/'.$slice->getSlot().
					' (id/clang/rev: '.$this->getId().'/'.$this->getClang().'/'.$this->getRevision().')',
					E_USER_WARNING
				);
				continue;
			}

			$content .= $slice->getOutput();
		}

		return $content;
	}

	public function getSlices($slot = null) {
		$service = sly_Core::getContainer()->getArticleSliceService();

		return $service->findByArticle($this, $slot);
	}

	public function countSlices($slot = null) {
		return count($this->getSlices($slot));
	}

	/**
	 * returns the rendered template with the articlecontent
	 *
	 * @return string
	 */
	public function getArticleTemplate() {
		if ($this->hasType()) {
			$params['article'] = $this;
			ob_start();
			ob_implicit_flush(0);
			sly_Util_Template::render($this->getTemplateName(), $params);
			$content = ob_get_clean();
		}
		else {
			$content = t('no_articletype_set');
		}

		return $content;
	}
}
