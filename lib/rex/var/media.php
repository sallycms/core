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

	const MEDIA     = 'REX_MEDIA';
	const MEDIALIST = 'REX_MEDIALIST';
	const FILE      = 'REX_FILE';
	const FILELIST  = 'REX_FILELIST';

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
		$content = $this->matchMediaButton($slice_id, $content);
		$content = $this->matchMediaListButton($slice_id, $content);
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
	 * MediaButton für die Eingabe
	 */
	public function matchMediaButton($slice_id, $content) {
		$vars = array('REX_FILE_BUTTON', 'REX_MEDIA_BUTTON');

		foreach ($vars as $var) {
			$matches = $this->getVarParams($content, $var);

			foreach ($matches as $match) {
				list ($param_str, $args) = $match;
				list ($id, $args)        = $this->extractArg('id', $args, 0);
				list ($category, $args)  = $this->extractArg('category', $args, '');

				$replace = $this->getMediaButton($id, $category, $args);
				$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
				$content = str_replace($var.'['.$param_str.']', $replace, $content);
			}
		}

		return $content;
	}

	/**
	 * MediaListButton für die Eingabe
	 */
	public function matchMediaListButton($slice_id, $content) {
		$vars    = array('REX_FILELIST_BUTTON', 'REX_MEDIALIST_BUTTON');
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($vars as $var) {
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

				$replace = $this->getMedialistButton($id, $value, $category, $args);
				$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
				$content = str_replace($var.'['.$param_str.']', $replace, $content);
			}
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMedia($slice_id, $content) {
		$vars    = array(self::FILE, self::MEDIA);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($vars as $var) {
			$matches = $this->getVarParams($content, $var);

			foreach ($matches as $match) {
				list ($param_str, $args) = $match;
				list ($id, $args)        = $this->extractArg('id', $args, 0);

				$value = $service->findBySliceTypeFinder($slice_id, self::MEDIA, $id);
				$value = $value ? $value->getValue() : '';

				// Mimetype ausgeben
				if (isset($args['mimetype'])) {
					$OOM = OOMedia::getMediaByName($value);
					if ($OOM) $replace = $OOM->getType();
				}
				// "normale" Ausgabe
				else {
					$replace = $value;
				}

				$replace = $this->handleGlobalVarParams($var, $args, $replace);
				$content = str_replace($var.'['.$param_str.']', $replace, $content);
			}
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchMediaList($slice_id, $content) {
		$vars    = array(self::FILELIST, self::MEDIALIST);
		$service = sly_Service_Factory::getSliceValueService();

		foreach ($vars as $var) {
			$matches = $this->getVarParams($content, $var);

			foreach ($matches as $match) {
				list ($param_str, $args) = $match;
				list ($id, $args) = $this->extractArg('id', $args, 0);

				$value = $service->findBySliceTypeFinder($slice_id, self::MEDIALIST, $id);
				$value = $value ? $value->getValue() : '';

				$replace = $this->handleGlobalVarParams($var, $args, $value);
				$content = str_replace($var.'['.$param_str.']', $replace, $content);
			}
		}

		return $content;
	}

	/**
	 * Gibt das Button Template zurück
	 */
	public function getMediaButton($id, $category = '', $args = array()) {
		$open_params = '';

		if ($category != '') {
			$open_params = '&amp;rex_file_category='.$category;
		}

		foreach ($args as $aname => $avalue) {
			$open_params .= '&amp;args['.urlencode($aname).']='.urlencode($avalue);
		}

		$wdgtClass = 'rex-widget-media';
		$service   = sly_Service_Factory::getAddOnService();

		// TODO: image_resize aus dem Core entfernen
		if (isset($args['preview']) && $args['preview'] && $service->isAvailable('image_resize')) {
			$wdgtClass .= ' rex-widget-preview';
		}

		$button = new sly_Form_Widget_MediaButton('MEDIA['.$id.']', null, 'REX_MEDIA['.$id.']', $id);
		$widget = '
		<div class="rex-widget">'
		.$button->render().
		'</div>';

		return $widget;
	}

	/**
	 * Gibt das ListButton Template zurück
	 */
	public function getMedialistButton($id, $value, $category = '', $args = array()) {
		$open_params = '';

		if ($category != '') {
			$open_params = '&amp;rex_file_category='.$category;
		}

		foreach ($args as $aname => $avalue) {
			$open_params .= '&amp;args['. $aname .']='. urlencode($avalue);
		}

		$wdgtClass = 'rex-widget-medialist';
		$service   = sly_Service_Factory::getAddOnService();

		// TODO: image_resize aus dem Core entfernen
		if (isset($args['preview']) && $args['preview'] && $service->isAvailable('image_resize')) {
			$wdgtClass .= ' rex-widget-preview';
		}

		$options        = '';
		$medialistarray = explode(',', $value);

		if (is_array($medialistarray)) {
			foreach ($medialistarray as $file) {
				if ($file != '') {
					$options .= '<option value="'.$file.'">'.$file.'</option>';
				}
			}
		}

		$button = new sly_Form_Widget_MediaListButton('MEDIALIST['.$id.']', null, $medialistarray, $id);
		$widget = '
		<div class="rex-widget">'
		.$button->render().
		'</div>';

		return $widget;
	}
}
