<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_MEDIA[1],
 * REX_MEDIALIST[1],
 * REX_MEDIA_WIDGET[1],
 * REX_MEDIALIST_WIDGET[1]
 *
 * @ingroup redaxo
 */
class rex_var_media extends rex_var {
	// --------------------------------- Actions

	const MEDIA     = 'REX_MEDIA';
	const MEDIALIST = 'REX_MEDIALIST';

	public function getRequestValues($REX_ACTION) {
		foreach (array('MEDIA', 'MEDIALIST') as $type) {
			$media = sly_request($type, 'array');
			$type  = 'REX_'.$type;

			foreach ($media as $key => $value) {
				$REX_ACTION[$type][$key] = $value;
			}
		}

		return $REX_ACTION;
	}

	public function getDatabaseValues($REX_ACTION, $slice_id) {
		$service = sly_Service_Factory::getSliceValueService();

		foreach (array('REX_MEDIA', 'REX_MEDIALIST') as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$REX_ACTION[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $REX_ACTION;
	}

	public function setSliceValues($slice_id, $REX_ACTION) {
		$slice = sly_Service_Factory::getSliceService()->findById($slice_id);

		foreach (array('REX_MEDIA', 'REX_MEDIALIST') as $type) {
			if (isset($REX_ACTION[$type])) {
				foreach ($REX_ACTION[$type] as $key => $value) {
					$slice->addValue($type, $key, $value);
				}
			}
		}
	}

	// --------------------------------- Output

	public function getBEInput($slice_id, $content) {
		$content = $this->matchMediaWidget($slice_id, $content);
		$content = $this->matchMediaListWidget($slice_id, $content);
		$content = $this->getOutput($slice_id, $content);
		return $content;
	}

	/**
	 * Ersetzt die Value Platzhalter
	 */
	public function getOutput($slice_id, $content) {
		$content = $this->matchMedia($slice_id, $content);
		$content = $this->matchMediaList($slice_id, $content);
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
	public function matchMediaWidget($slice_id, $content) {
		$var     = 'REX_MEDIA_WIDGET';
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, '');

			$value = $service->findBySliceTypeFinder($slice_id, self::MEDIA, $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->getMediaWidget($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * MediaListWidget für die Eingabe
	 */
	public function matchMediaListWidget($slice_id, $content) {
		$var     = 'REX_MEDIALIST_WIDGET';
		$service = sly_Service_Factory::getSliceValueService();
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value    = $service->findBySliceTypeFinder($slice_id, self::MEDIALIST, $id);
			$value    = $value ? $value->getValue() : '';
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
	public function matchMedia($slice_id, $content) {
		$var     = self::MEDIA;
		$service = sly_Service_Factory::getSliceValueService();
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, self::MEDIA, $id);
			$value = $value ? $value->getValue() : '';

			// Mimetype ausgeben
			if (isset($args['mimetype'])) {
				$medium = sly_Util_Medium::findByFilename($value);
				if ($medium) $replace = $medium->getType();
			}
			// "normale" Ausgabe
			else {
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
	public function matchMediaList($slice_id, $content) {
		$var     = self::MEDIALIST;
		$service = sly_Service_Factory::getSliceValueService();
		$matches = $this->getVarParams($content, $var);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, self::MEDIALIST, $id);
			$value = $value ? $value->getValue() : '';

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
		$widget = '<div class="rex-widget">'.$widget->render().'</div>';

		return $widget;
	}

	/**
	 * Gibt das ListWidget Template zurück
	 */
	public function getMedialistWidget($id, $value, $category = '', $args = array()) {
		// TODO: Build something like $widget->setRootCat($category);

		$files  = array_filter(explode(',', $value));
		$widget = new sly_Form_Widget_MediaList('MEDIALIST['.$id.']', null, $medialistarray, $id);
		$widget = '<div class="rex-widget">'.$widget->render().'</div>';

		return $widget;
	}
}
