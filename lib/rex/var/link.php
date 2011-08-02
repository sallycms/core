<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * SLY_LINK_WIDGET,
 * SLY_LINK,
 * SLY_LINK_URL,
 * SLY_LINKLIST_WIDGET,
 * SLY_LINKLIST
 *
 * @ingroup redaxo
 */
class rex_var_link extends rex_var {

	const LINK           = 'SLY_LINK';
	const LINKURL        = 'SLY_LINK_URL';
	const LINKLIST       = 'SLY_LINKLIST';
	const LINKWIDGET     = 'SLY_LINK_WIDGET';
	const LINKLISTWIDGET = 'SLY_LINKLIST_WIDGET';

	public function getRequestValues($REX_ACTION) {
		foreach (array('LINK', 'LINKLIST') as $type) {
			$link = sly_requestArray($type, 'string');
			$type = 'SLY_'.$type;

			foreach ($link as $key => $value) {
				$REX_ACTION[$type][$key] = $value;
			}
		}

		return $REX_ACTION;
	}

	public function getDatabaseValues($slice_id) {
		$service = sly_Service_Factory::getSliceValueService();
		$data    = array();

		foreach (array(self::LINK, self::LINKLIST) as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$data[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $data;
	}

	public function setSliceValues($REX_ACTION, $slice_id) {
		$slice = sly_Service_Factory::getSliceService()->findById($slice_id);

		foreach (array(self::LINK, self::LINKLIST) as $type) {
			if (isset($REX_ACTION[$type])) {
				foreach ($REX_ACTION[$type] as $key => $value){
					$slice->addValue($type, $key, $value);
				}
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->getOutput($REX_ACTION, $content);
		$content = $this->matchLinkWidget($REX_ACTION, $content);
		$content = $this->matchLinkListWidget($REX_ACTION, $content);

		return $content;
	}

	public function getOutput($REX_ACTION, $content) {
		$content = $this->matchLinkList($REX_ACTION, $content);
		$content = $this->matchLink($REX_ACTION, $content);
		$content = $this->matchLinkUrl($REX_ACTION, $content);

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
	 * Widget für die Eingabe
	 */
	public function matchLinkWidget($REX_ACTION, $content) {
		$def_category = '';
		$article_id   = sly_request('article_id', 'int');

		if ($article_id != 0) {
			$art          = sly_Util_Article::findById($article_id);
			$def_category = $art->getCategoryId();
		}

		$var     = self::LINKWIDGET;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::LINK][$id]) ? strval($REX_ACTION[self::LINK][$id]) : '';

			// Wenn vom Programmierer keine Kategorie vorgegeben wurde,
			// die Linkmap mit der aktuellen Kategorie öffnen
			list ($category, $args) = $this->extractArg('category', $args, $def_category);

			$replace = $this->getLinkWidget($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Widget für die Eingabe
	 */
	public function matchLinkListWidget($REX_ACTION, $content) {
		$var     = self::LINKLISTWIDGET;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, 0);

			$value = isset($REX_ACTION[self::LINKLIST][$id]) ? strval($REX_ACTION[self::LINKLIST][$id]) : '';

			$replace = $this->getLinklistWidget($id, $value, $category);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLink($REX_ACTION, $content) {
		$var     = self::LINK;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::LINK][$id]) ? strval($REX_ACTION[self::LINK][$id]) : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkUrl($REX_ACTION, $content) {
		$var     = self::LINKURL;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::LINK][$id]) ? strval($REX_ACTION[self::LINK][$id]) : '';

			$replace = $value === '' ? '' : sly_Util_Article::findById($value)->getUrl();
			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkList($REX_ACTION, $content) {
		$var     = self::LINKLIST;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::LINKLIST][$id]) ? strval($REX_ACTION[self::LINKLIST][$id]) : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Gibt das Widget Template zurück
	 */
	public function getLinkWidget($id, $article_id, $category = '') {
		// TODO: Build something like $widget->setRootCat($category);
		$widget = new sly_Form_Widget_Link(self::LINK.'['.$id.']', null, $article_id, $id);
		$widget = '<div class="rex-widget">'.$widget->render().'</div>';

		return $widget;
	}

	/**
	 * Gibt das ListWidget Template zurück
	 */
	public function getLinklistWidget($id, $value, $category = '') {
		// TODO: Build something like $widget->setRootCat($category);
		$articles = explode(',', $value);
		$widget   = new sly_Form_Widget_LinkList(self::LINKLIST.'['.$id.']', null, $articles, $id);
		$widget   = '<div class="rex-widget">'.$widget->render().'</div>';

		return $widget;
	}
}
