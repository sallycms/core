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
 * Base class for complex widgets
 *
 * 'Widgets' is the collective term for linkbuttons, mediabuttons etc.
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Widget extends sly_Form_ElementBase {
	protected $namespace; ///< string  the namespace

	private static $idOffset = 0; ///< int current system wide offset for widget IDs

	/**
	 * Constructor
	 *
	 * @param string $label      the element name
	 * @param string $label      the label
	 * @param string $value      the current widget value
	 * @param string $id         optional ID
	 * @param string $namespace  namespace ("linkbutton" or something like that)
	 */
	public function __construct($name, $label, $value, $id, $namespace) {
		parent::__construct($name, $label, $value, $id);
		$this->namespace = 'sly.form.widget.'.$namespace;
	}

	/**
	 * Get unique element ID
	 *
	 * This method returns the current ID for the given namespace. The ID is
	 * incremented every time a widget is rendered (so multilingual in rendering
	 * the N elements will each get a new ID).
	 *
	 * @return int  current ID for the element namespace (starting at 1)
	 */
	public function getWidgetID() {
		$registry = sly_Core::getTempRegistry();
		$key      = $this->namespace.'.counter';

		if (!$registry->has($key)) {
			$registry->set($key, 1);
		}

		return self::$idOffset + $registry->get($key);
	}

	/**
	 * Increments the current widget ID
	 *
	 * This method is called when rendering a widget. Its purpose is to generate
	 * new IDs so that the elements don't collide, even when created by distinct
	 * parts of the application (like linkbuttons in both metainfos and module
	 * inputs).
	 */
	public function consumeWidgetID() {
		$registry = sly_Core::getTempRegistry();
		$key      = $this->namespace.'.counter';
		$lastID   = $registry->get($key, 1);

		$registry->set($key, $lastID + 1);
	}

	/**
	 * Set the widget ID offset
	 *
	 * This method can be called when mutliple forms appear on one page, but were
	 * not created during the same request. This is true for all forms that use
	 * AJAX (or addOns that display forms in tabs, like varisale).
	 *
	 * To ensure uniqueness, callers can give something like the current
	 * timestamp (modulo 99999) or a random value. All newly generated IDs will
	 * then start from the given offset.
	 *
	 * @param int $offset  the new offset
	 */
	public static function setOffset($offset) {
		self::$idOffset = abs((int) $offset);
	}
}
