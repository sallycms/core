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
 * Translation class
 *
 * This class implements the basic translation method used in Sally. An instance
 * is created by loading a given locale file (and maybe appending more later
 * on). Translations can be accessed via the msg() function or by using the
 * global function t().
 *
 * Note that the locales used by this class having *nothing* to do with the
 * frontend languages (clangs), so beware that when using translation in your
 * frontend.
 *
 * @ingroup i18n
 * @author  Christoph
 * @since   0.3
 */
class sly_I18N implements sly_I18N_Base {
	protected $paths;
	protected $locale;
	protected $texts;

	/**
	 * Constructor
	 *
	 * Creates the instance, loads the language file and optionally sets the
	 * locale.
	 *
	 * @param string  $locale     locale name (like 'de_de')
	 * @param string  $path       path to .yml files (can be null to not load any language files yet)
	 * @param boolean $setlocale  when true the locale will be set via setlocale()
	 */
	public function __construct($locale, $path, $setlocale = true) {
		$this->texts  = array();
		$this->paths  = array();
		$this->locale = $locale;

		if ($path !== null) {
			$this->appendFile($path);
		}

		if ($setlocale) {
			$this->setPHPLocale();
		}
	}

	/**
	 * Set the system locale
	 *
	 * This method will call setlocale() with the locale string given in the
	 * loaded language file. Do this only once, since it hurts performance and
	 * you don't need to change it at runtime anways.
	 */
	public function setPHPLocale() {
		$locales = array();

		foreach (explode(',', $this->msg('setlocale')) as $locale) {
			$locales[] = $locale.'.UTF-8';
			$locales[] = $locale.'.UTF8';
			$locales[] = $locale.'.utf-8';
			$locales[] = $locale.'.utf8';
			$locales[] = $locale;
		}

		setlocale(LC_ALL, $locales);
	}

	/**
	 * Append a language file to the object
	 *
	 * This method looks in the given path for a matching language file (matching
	 * in regard to the locale of this object) and appends its messages to the
	 * internal list of messages.
	 *
	 * @param  string $path  path to look in for the matching YAML file
	 * @return boolean       true if the file was found, else false
	 */
	public function appendFile($path, $prefix = '') {
		// if the file is already included return
		if(in_array($path, $this->paths)) return true;

		$this->paths[] = $path;
		$filename      = $path.'/'.$this->locale.'.yml';

		if (is_readable($filename)) {
			$lines = sly_Util_YAML::load($filename);

			foreach ($lines as $key => $value) {
				$this->texts[$prefix.$key] = $value;
			}

			return true;
		}

		return false;
	}

	/**
	 * Adds a custom translation key on the current object
	 *
	 * @param string $key          key to translate
	 * @param string $translation  translation
	 */
	public function setMessage($key, $translation) {
		$this->texts[$key] = $translation;
	}

	/**
	 * Translate a key
	 *
	 * Looks in the message list for a matching key and returns the message. This
	 * method can be called with more than one argument, which will be used as
	 * replacements for placeholders like {0}, {1} and so on.
	 *
	 * @param  string $key  key to translate
	 * @return string       translated message or the key like in translate:key when not found
	 */
	public function msg($key) {
		//var_dump($this->locale);
		//var_dump($this->paths);
		if (!$this->hasMsg($key)) {
			return '[translate:'.$key.']';
		}

		$msg          = $this->texts[$key];
		$patterns     = array();
		$replacements = array();

		$argv = func_get_args();
		$argc = count($argv);

		// Wir überspringen $key -> $i = 1
		for ($i = 1; $i < $argc; ++$i) {
			$patterns[]     = '{'.($i-1).'}';
			$replacements[] = $argv[$i];
		}

		return str_replace($patterns, $replacements, $msg);
	}

	/**
	 * Check for a key
	 *
	 * @param  string $key key to look for
	 * @return boolean     true if the key exists, else false
	 */
	public function hasMsg($key) {
		return isset($this->texts[$key]);
	}

	/**
	 * Get a list of all locales
	 *
	 * This method scans the given path for all language files and returns a list
	 * of locales (filenames without the extension). The results will be cached
	 * for the duration of the script execution.
	 *
	 * @param  string $path  path to look in
	 * @return array         list of locales (unsorted)
	 */
	public static function getLocales($path) {
		static $cache = array();

		$path = realpath($path);

		if ($path === false) {
			return array();
		}

		if (!isset($cache[$path])) {
			$locales = array();
			$files   = glob($path.'/*.yml');

			foreach ($files as $filename) {
				// von 'C:\foo\bar.yml' nur 'bar' speichern
				$locales[] = substr(basename($filename), 0, -4);
			}

			$cache[$path] = $locales;
		}

		return $cache[$path];
	}

	/**
	 * Return all translations
	 *
	 * @return array  plain list of translations
	 */
	public function getMessages() {
		return $this->texts;
	}

	/**
	 * Return the locale code of this instance
	 *
	 * @return string  the locale code (like 'de_de')
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Reset the internal locale code
	 *
	 * Use wisely, my friend... with great power comes great mustache!
	 *
	 * @param string $locale  the new locale code (like 'de_de')
	 */
	public function setLocale($locale) {
		$paths        = $this->paths;
		$this->locale = $locale;
		$this->texts  = array();
		$this->paths  = array();
		foreach ($paths as $path) {
			$this->appendFile($path);
		}
	}
}
