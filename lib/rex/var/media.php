<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_FILE[1],
 * REX_FILELIST[1],
 * REX_FILE_BUTTON[1],
 * REX_FILELIST_BUTTON[1],
 * REX_MEDIA[1],
 * REX_MEDIALIST[1],
 * REX_MEDIA_BUTTON[1],
 * REX_MEDIALIST_BUTTON[1]
 *
 * Alle Variablen die mit REX_FILE beginnnen sind als deprecated anzusehen!
 *
 * @ingroup redaxo
 */
class rex_var_media extends rex_var {
	// --------------------------------- Actions

	const MEDIA           = 'REX_MEDIA';
	const MEDIALIST       = 'REX_MEDIALIST';
	const MEDIABUTTON     = 'REX_MEDIA_BUTTON';
	const MEDIALISTBUTTON = 'REX_MEDIALIST_BUTTON';

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

	public function getDatabaseValues($slice_id) {
		$service = sly_Service_Factory::getSliceValueService();
		$data = array();
		foreach (array('REX_MEDIA', 'REX_MEDIALIST') as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$data[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $data;
	}

	public function setSliceValues($REX_ACTION, $slice_id) {
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

	public function getBEInput($REX_ACTION, $content) {
		$content = $this->matchMediaButton($REX_ACTION, $content);
		$content = $this->matchMediaListButton($REX_ACTION, $content);
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
	 * MediaButton für die Eingabe
	 */
	public function matchMediaButton($REX_ACTION, $content) {

		$matches = $this->getVarParams($content, self::MEDIABUTTON);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, '');

			$value = isset($REX_ACTION[self::MEDIA][$id]) ? strval($REX_ACTION[self::MEDIA][$id]) : '';

			$replace = $this->getMediaButton($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams(self::MEDIABUTTON, $args, $replace);
			$content = str_replace(self::MEDIABUTTON.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * MediaListButton für die Eingabe
	 */
	public function matchMediaListButton($slice_id, $content) {
		$matches = $this->getVarParams($content, self::MEDIALISTBUTTON);

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = isset($REX_ACTION[self::MEDIALIST][$id]) ? strval($REX_ACTION[self::MEDIALIST][$id]) : '';
			$category = '';

			if (isset($args['category'])) {
				$category = $args['category'];
				unset($args['category']);
			}

			$replace = $this->getMedialistButton($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams(self::MEDIALISTBUTTON, $args, $replace);
			$content = str_replace(self::MEDIALISTBUTTON.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMedia($slice_id, $content) {
			$matches = $this->getVarParams($content, self::MEDIA);

			foreach ($matches as $match) {
				list ($param_str, $args) = $match;
				list ($id, $args)        = $this->extractArg('id', $args, 0);

				$value = isset($REX_ACTION[self::MEDIA][$id]) ? strval($REX_ACTION[self::MEDIA][$id]) : '';

				// Mimetype ausgeben
				if (isset($args['mimetype'])) {
					$medium = sly_Util_Medium::findByFilename($value);
					if ($medium) $replace = $medium->getType();
				}
				// "normale" Ausgabe
				else {
					$replace = $value;
				}

				$replace = $this->handleGlobalVarParams(self::MEDIA, $args, $replace);
				$content = str_replace(self::MEDIA.'['.$param_str.']', $replace, $content);
			}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMediaList($slice_id, $content) {
			$matches = $this->getVarParams($content, self::MEDIALIST);

			foreach ($matches as $match) {
				list ($param_str, $args) = $match;
				list ($id, $args) = $this->extractArg('id', $args, 0);

				$value = isset($REX_ACTION[self::MEDIALIST][$id]) ? strval($REX_ACTION[self::MEDIALIST][$id]) : '';

				$replace = $this->handleGlobalVarParams(self::MEDIALIST, $args, $value);
				$content = str_replace(self::MEDIALIST.'['.$param_str.']', $replace, $content);
			}

		return $content;
	}

	/**
	 * Gibt das Button Template zurück
	 */
	public function getMediaButton($id, $value, $category = '', $args = array()) {
		// TODO: Build something like $button->setRootCat($category);

		$button = new sly_Form_Widget_MediaButton('MEDIA['.$id.']', null, $value, $id);
		$widget = '<div class="rex-widget">'.$button->render().'</div>';

		return $widget;
	}

	/**
	 * Gibt das ListButton Template zurück
	 */
	public function getMedialistButton($id, $value, $category = '', $args = array()) {
		// TODO: Build something like $button->setRootCat($category);

		$files  = array_filter(explode(',', $value));
		$button = new sly_Form_Widget_MediaListButton('MEDIALIST['.$id.']', null, $medialistarray, $id);
		$widget = '<div class="rex-widget">'.$button->render().'</div>';

		return $widget;
	}
}
