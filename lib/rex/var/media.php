<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * SLY_MEDIA[1],
 * SLY_MEDIALIST[1],
 * SLY_MEDIA_WIDGET[1],
 * SLY_MEDIALIST_WIDGET[1]
 *
 * @ingroup redaxo
 */
class rex_var_media extends rex_var {
	// --------------------------------- Actions

	const MEDIA           = 'SLY_MEDIA';
	const MEDIALIST       = 'SLY_MEDIALIST';
	const MEDIAWIDGET     = 'SLY_MEDIA_WIDGET';
	const MEDIALISTWIDGET = 'SLY_MEDIALIST_WIDGET';

	public function getRequestValues($REX_ACTION) {
		foreach (array('MEDIA', 'MEDIALIST') as $type) {
			$media = sly_request($type, 'array');
			$type  = 'SLY_'.$type;

			foreach ($media as $key => $value) {
				$REX_ACTION[$type][$key] = $value;
			}
		}

		return $REX_ACTION;
	}

	public function getDatabaseValues($slice_id) {
		$service = sly_Service_Factory::getSliceValueService();
		$data = array();
		foreach (array('MEDIA', 'MEDIALIST') as $type) {
			$type  = 'SLY_'.$type;
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$data[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $data;
	}

	public function setSliceValues($REX_ACTION, $slice_id) {
		$slice = sly_Service_Factory::getSliceService()->findById($slice_id);

		foreach (array('SLY_MEDIA', 'SLY_MEDIALIST') as $type) {
			if (isset($REX_ACTION[$type])) {
				foreach ($REX_ACTION[$type] as $key => $value) {
					$slice->addValue($type, $key, $value);
				}
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->matchMediaWidget($REX_ACTION, $content);
		$content = $this->matchMediaListWidget($REX_ACTION, $content);
		$content = $this->getOutput($REX_ACTION, $content);
		return $content;
	}

	/**
	 * Ersetzt die Value Platzhalter
	 */
	public function getOutput($REX_ACTION, $content) {
		$content = $this->matchMedia($REX_ACTION, $content);
		$content = $this->matchMediaList($REX_ACTION, $content);
		return $content;
	}

	protected function handleDefaultParam($varname, $args, $name, $value) {
		switch ($name) {
			case '1':
			case 'category':
				$args['category'] = (int) $value;
				break;

			case 'types':
				$args[$name] = (string) $value;
				break;

			case 'preview':
				$args[$name] = (boolean) $value;
				break;

			case 'mimetype':
				$args[$name] = (string) $value;
		}

		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * MediaWidget für die Eingabe
	 */
	public function matchMediaWidget($REX_ACTION, $content) {
		$var = self::MEDIAWIDGET;
		$matches = $this->getVarParams($content, self::MEDIAWIDGET);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, '');

			$value = isset($REX_ACTION[self::MEDIA][$id]) ? strval($REX_ACTION[self::MEDIA][$id]) : '';

			$replace = $this->getMediaWidget($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * MediaListWidget für die Eingabe
	 */
	public function matchMediaListWidget($REX_ACTION, $content) {
		$var = self::MEDIALISTWIDGET;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::MEDIALIST][$id]) ? strval($REX_ACTION[self::MEDIALIST][$id]) : '';
			$category = '';

			if (isset($args['category'])) {
				$category = $args['category'];
				unset($args['category']);
			}

			$replace = $this->getMedialistWidget($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMedia($REX_ACTION, $content) {
		$var = self::MEDIA;
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);
			
			$value = isset($REX_ACTION[$var][$id]) ? strval($REX_ACTION[$var][$id]) : '';

			// Mimetype ausgeben
			if (isset($args['mimetype'])) {
				$medium = sly_Util_Medium::findByFilename($value);
				if ($medium) $replace = $medium->getType();
			}else {
				$replace = $value;
			}

			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}
		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMediaList($REX_ACTION, $content) {
		$var = self::MEDIALIST;
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[$var][$id]) ? strval($REX_ACTION[$var][$id]) : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}
		return $content;
	}

	/**
	 * Gibt das Widget Template zurück
	 */
	public function getMediaWidget($id, $value, $category = '', $args = array()) {
		// TODO: Build something like $widget->setRootCat($category);

		$widget = new sly_Form_Widget_Media('MEDIA['.$id.']', null, $value, $id);

		return $widget->render();
	}

	/**
	 * Gibt das ListWidget Template zurück
	 */
	public function getMedialistWidget($id, $value, $category = '', $args = array()) {
		// TODO: Build something like $widget->setRootCat($category);

		$files  = array_filter(explode(',', $value));
		$widget = new sly_Form_Widget_MediaList('MEDIALIST['.$id.']', null, $files, $id);

		return $widget->render();
	}
}
