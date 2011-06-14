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
 * @ingroup i18n
 * @author  Christoph
 * @since   0.3
 */
class sly_I18N implements sly_I18N_Base {
	protected $locales;
	protected $searchpath;
	protected $locale;
	protected $texts;
	protected $text_loaded;

	/**
	 * Constructor
	 *
	 * @param string  $locale      locale name (like 'de_de')
	 * @param string  $searchpath  path to .yml file
	 * @param boolean $setlocale   when true the locale will be set via setlocale()
	 */
	public function __construct($locale, $searchpath, $setlocale = true) {
		$this->searchpath  = $searchpath;
		$this->texts       = array();
		$this->locale      = $locale;
		$this->locales     = null;
		$this->text_loaded = false;

		if ($setlocale) {
			$this->setLocale();
		}
	}

	/**
	 * setlocale() wrapper
	 */
	public function setLocale() {
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
	 * Lädt alle Übersetzungen der aktuellen Sprache aus dem Sprachpfad und fügt diese dem Katalog hinzu.
	 */
	public function loadTexts() {
		if ($this->appendFile($this->searchpath)) {
			$this->text_loaded = true;
		}
	}

	/**
	 * Sucht im angegebenden Ordner nach eine Sprachdatei der aktuellen Sprache und f�gt diese dem Sprachkatalog an
	 *
	 * @param  string $searchPath  Pfad in dem die Sprachdatei gesucht werden soll
	 * @return boolean             true if the file was read, else false
	 */
	public function appendFile($searchPath, $prefix = '') {
		$filename = $searchPath.'/'.$this->locale.'.yml';

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
	 * Durchsucht den Sprachkatalog nach einem Schlüssel und gibt die dazugehörige Übersetzung zurück
	 *
	 * @param  string $key  zu suchender Schlüssel
	 * @return string
	 */
	public function msg($key) {
		if (!$this->text_loaded) {
			$this->loadTexts();
		}

		if ($this->hasMsg($key)) {
			$msg = $this->texts[$key];
		}
		else {
			$msg = '[translate:'.$key.']';
		}

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
	 * Fügt dem Sprachkatalog unter dem gegebenen Schlüssel eine neue Übersetzung hinzu
	 *
	 * @param string $key  Schlüssel unter dem die Übersetzung abgelegt wird
	 * @param string $msg  übersetzter Text
	 */
	public function addMsg($key, $msg) {
		$this->texts[$key] = $msg;
	}

	/**
	 * Prüft ob der Sprachkatalog zu dem gegebenen Schlüssel eine Übersetzung beinhaltet
	 *
	 * @param  string $key zu suchender Schlüssel
	 * @return boolean     true wenn der Schlüssel gefunden wurde, sonst false
	 */
	public function hasMsg($key) {
		return isset($this->texts[$key]);
	}

	/**
	 * Durchsucht den Searchpath nach allen verfügbaren Sprachdateien und gibt diese zurück
	 *
	 * @param  string $searchpath  zu duruchsuchender Ordner
	 * @return array               Array von gefundenen Sprachen (locales)
	 */
	public static function getLocales($searchpath) {
		static $cache = array();

		$searchpath = realpath($searchpath);

		if ($searchpath !== false && !isset($cache[$searchpath])) {
			$locales = array();
			$files   = glob($searchpath.'/*.yml');

			foreach ($files as $filename) {
				// von 'C:\foo\bar.yml' nur 'bar' speichern
				$locales[] = substr(basename($filename), 0, -4);
			}

			$cache[$searchpath] = $locales;
		}

		return $cache[$searchpath];
	}

	/**
	 * Return the locale code of this instance
	 *
	 * @return string  the locale code (like 'de_de')
	 */
	public function getLocale() {
		return $this->locale;
	}
}
