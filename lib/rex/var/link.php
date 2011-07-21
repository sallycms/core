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
	
	const LINK           = 'REX_LINK';
	const LINKID        = 'REX_LINK_ID';
	const LINKLIST       = 'REX_LINKLIST';
	const LINKBUTTON     = 'REX_LINK_BUTTON';
	const LINKLISTBUTTON = 'REX_LINKLIST_BUTTON';

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

	public function getDatabaseValues($slice_id) {
		$service = sly_Service_Factory::getSliceValueService();
		$data = array();
		foreach (array('REX_LINK', 'REX_LINKLIST') as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$data[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $data;
	}

	public function setSliceValues($REX_ACTION, $slice_id) {
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

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->getOutput($REX_ACTION, $content);
		$content = $this->matchLinkButton($REX_ACTION, $content);
		$content = $this->matchLinkListButton($REX_ACTION, $content);

		return $content;
	}

	public function getOutput($REX_ACTION, $content) {
		$content = $this->matchLinkList($REX_ACTION, $content);
		$content = $this->matchLink($REX_ACTION, $content);
		$content = $this->matchLinkId($REX_ACTION, $content);

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
	public function matchLinkButton($REX_ACTION, $content) {
		$def_category = '';
		$article_id   = sly_request('article_id', 'int');

		if ($article_id != 0) {
			$art          = sly_Util_Article::findById($article_id);
			$def_category = $art->getCategoryId();
		}

		$var     = self::LINKBUTTON;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::LINK][$id]) ? strval($REX_ACTION[self::LINK][$id]) : '';

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
	public function matchLinkListButton($REX_ACTION, $content) {
		$var     = self::LINKLISTBUTTON;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, 0);

			$value = isset($REX_ACTION[self::LINKLIST][$id]) ? strval($REX_ACTION[self::LINKLIST][$id]) : '';

			$replace = $this->getLinklistButton($id, $value, $category);
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

			$replace = $value === '' ? '' : sly_Util_Article::findById($value)->getUrl();
			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkId($REX_ACTION, $content) {
		$var     = self::LINKID;
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
