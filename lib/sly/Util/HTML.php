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
 * @ingroup util
 */
class sly_Util_HTML {
	/**
	 * Builds an attribute part for a tag and returns it as a string.
	 *
	 * @param  array  $attributes  Associative array of attribute values, where key is the attribute name and value is the attribute value.
	 *                             e.g. array('src' => 'picture.png', alt='my picture')
	 * @param  array  $force       Array of attributes that should be added, even if they are empty.
	 *                             e.g. array('alt')
	 * @return string              String with the attributes and their values
	 */
	public static function buildAttributeString($attributes, $force = array()) {
		$attributes = array_filter($attributes, array(__CLASS__, 'isAttribute'));

		foreach ($force as $attribute) {
			if (empty($attributes[$attribute])) $attributes[$attribute] = '';
		}

		foreach ($attributes as $key => &$value) {
			$value = strtolower(trim($key)).'="'.sly_html(trim($value)).'"';
		}

		return implode(' ', $attributes);
	}

	/**
	 * @param  string $target
	 * @param  string $text
	 * @param  string $class
	 * @return string
	 */
	public static function getSpriteLink($target, $text, $class) {
		if (empty($target)) {
			$span = array('class' => 'sly-sprite sly-sprite-'.$class);
			return sprintf('<span %s><span>%s</span></span>', self::buildAttributeString($span), sly_html($text));
		}

		$a = array('href' => $target, 'class' => 'sly-sprite sly-sprite-'.$class);
		return sprintf('<a %s><span>%s</span></a>', self::buildAttributeString($a), sly_html($text));
	}

	public static function startJavaScript() {
		ob_start();
		print "<script type=\"text/javascript\">\n// <![CDATA[\n";
	}

	public static function endJavaScript() {
		print "\n// ]]>\n</script>";
		print ob_get_clean();
	}

	/**
	 * @param string $content
	 */
	public static function printJavaScript($content) {
		self::startJavaScript();
		print $content;
		self::endJavaScript();
	}

	public static function startOnDOMReady() {
		self::startJavaScript();
		print 'jQuery(function($) { ';
	}

	public static function endOnDOMReady() {
		print ' });';
		self::endJavaScript();
	}

	/**
	 * @param string $content
	 */
	public static function onDOMReady($content) {
		self::startOnDOMReady();
		print $content;
		self::endOnDOMReady();
	}

	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public static function isAttribute($value) {
		return $value !== false && strlen(trim($value)) > 0;
	}

	/**
	 * @param string $value
	 * @param string $key
	 */
	public static function concatValues(&$value, $key) {
		$value = strtolower(trim($key)).'="'.sly_html(trim($value)).'"';
	}
}
