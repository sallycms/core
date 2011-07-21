<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_LINK_BUTTON,
 * REX_LINK,
 * REX_LINK_ID,
 * REX_LINKLIST_BUTTON,
 * REX_LINKLIST
 *
 * @ingroup redaxo
 */
class rex_var_link extends rex_var {
	// --------------------------------- Actions

	public function getRequestValues($REX_ACTION) {
		foreach (array('LINK', 'LINKLIST') as $type) {
			$link = sly_requestArray($type, 'string');
			$type = 'REX_'.$type;

			foreach ($link as $key => $value) {
				$REX_ACTION[$type][$key] = $value;
			}
		}

		return $REX_ACTION;
	}

	public function getDatabaseValues($REX_ACTION, $slice_id) {
		$service = sly_Service_Factory::getSliceValueService();

		foreach (array('REX_LINK', 'REX_LINKLIST') as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$REX_ACTION[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $REX_ACTION;
	}

	public function setSliceValues($slice_id, $REX_ACTION) {
		$slice = sly_Service_Factory::getSliceService()->findById($slice_id);

		foreach (array('REX_LINK', 'REX_LINKLIST') as $type) {
			if (isset($REX_ACTION[$type])) {
				foreach ($REX_ACTION[$type] as $key => $value){
					$slice->addValue($type, $key, $value);
				}
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($slice_id, $content) {
		$content = $this->getOutput($slice_id, $content);
		$content = $this->matchLinkButton($slice_id, $content);
		$content = $this->matchLinkListButton($slice_id, $content);

		return $content;
	}

	public function getOutput($slice_id, $content) {
		$content = $this->matchLinkList($slice_id, $content);
		$content = $this->matchLink($slice_id, $content);
		$content = $this->matchLinkId($slice_id, $content);

		return $content;
	}

	protected function handleDefaultParam($varname, $args, $name, $value) {
		switch ($name) {
			case '1':
			case 'category':
				$args['category'] = (int) $value;
		}

		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * Button für die Eingabe
	 */
	public function matchLinkButton($slice_id, $content) {
		$def_category = '';
		$article_id   = sly_request('article_id', 'int');

		if ($article_id != 0) {
			$art          = sly_Util_Article::findById($article_id);
			$def_category = $art->getCategoryId();
		}

		$var     = 'REX_LINK_BUTTON';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			// Wenn vom Programmierer keine Kategorie vorgegeben wurde,
			// die Linkmap mit der aktuellen Kategorie öffnen
			list ($category, $args) = $this->extractArg('category', $args, $def_category);

			$replace = $this->getLinkButton($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Button für die Eingabe
	 */
	public function matchLinkListButton($slice_id, $content) {
		$var     = 'REX_LINKLIST_BUTTON';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->getLinklistButton($id, $value, $category);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLink($slice_id, $content) {
		$var     = 'REX_LINK';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $value === '' ? '' : sly_Util_Article::findById($value)->getUrl();
			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkId($slice_id, $content) {
		$var     = 'REX_LINK_ID';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkList($slice_id, $content) {
		$var     = 'REX_LINKLIST';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Gibt das Button Template zurück
	 */
	public function getLinkButton($id, $article_id, $category = '') {
		// TODO: Build something like $button->setRootCat($category);
		$button = new sly_Form_Widget_LinkButton('LINK['.$id.']', null, $article_id, $id);
		$widget = '<div class="rex-widget">'.$button->render().'</div>';

		return $widget;
	}

	/**
	 * Gibt das ListButton Template zurück
	 */
	public function getLinklistButton($id, $value, $category = '') {
		// TODO: Build something like $button->setRootCat($category);
		$articles = explode(',', $value);
		$button   = new sly_Form_Widget_LinkListButton('LINKLIST['.$id.']', null, $articles, $id);
		$widget   = '<div class="rex-widget">'.$button->render().'</div>';

		return $widget;
	}
}
