<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * System Configuration
 *
 * @ingroup core
 */
class sly_Configuration {
	const STORE_PROJECT         = 1; ///< int
	const STORE_LOCAL           = 2; ///< int
	const STORE_LOCAL_DEFAULT   = 3; ///< int
	const STORE_STATIC          = 4; ///< int
	const STORE_PROJECT_DEFAULT = 5; ///< int

	private $mode              = array(); ///< array
	private $loadedConfigFiles = array(); ///< array

	private $staticConfig;  ///< sly_Util_Array
	private $localConfig;   ///< sly_Util_Array
	private $projectConfig; ///< sly_Util_Array
	private $cache;         ///< sly_Util_Array

	private $localConfigModified   = false; ///< boolean
	private $projectConfigModified = false; ///< boolean

	public function __construct() {
		$this->staticConfig  = new sly_Util_Array();
		$this->localConfig   = new sly_Util_Array();
		$this->projectConfig = new sly_Util_Array();
		$this->cache         = null;
	}

	public function __destruct() {
		$this->flush();
	}

	/**
	 * @return string  the directory where the config is stored
	 */
	protected function getConfigDir() {
		static $protected = false;

		$dir = SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'config';

		if (!$protected) {
			sly_Util_Directory::createHttpProtected($dir, true);
		}

		$protected = true;
		return $dir;
	}

	/**
	 * @return string  the full path to the local config file
	 */
	protected function getLocalConfigFile() {
		return $this->getConfigDir().DIRECTORY_SEPARATOR.'sly_local.yml';
	}

	/**
	 * @return string  the full path to the project config file
	 */
	public function getProjectConfigFile() {
		return $this->getConfigDir().DIRECTORY_SEPARATOR.'sly_project.yml';
	}

	public function loadDevelop() {
		$dir = new sly_Util_Directory(SLY_DEVELOPFOLDER.DIRECTORY_SEPARATOR.'config');

		if ($dir->exists()) {
			foreach ($dir->listPlain() as $file) {
				$this->loadStatic($dir.DIRECTORY_SEPARATOR.$file);
			}
		}
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return mixed              false when an error occured, else the loaded configuration (most likely an array)
	 */
	public function loadProject($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	/**
	 * @throws sly_Exception     when something is fucked up (file not found, bad parameters, ...)
	 * @param  string $filename  the file to load
	 * @param  string $key       where to mount the loaded config
	 * @return mixed             false when an error occured, else the loaded configuration (most likely an array)
	 */
	public function loadStatic($filename, $key = '/') {
		return $this->loadInternal($filename, self::STORE_STATIC, false, $key);
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return mixed              false when an error occured, else the loaded configuration (most likely an array)
	 */
	public function loadLocalDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_LOCAL_DEFAULT, $force, $key);
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return mixed              false when an error occured, else the loaded configuration (most likely an array)
	 */
	public function loadProjectDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	public function loadLocalConfig() {
		$filename = $this->getLocalConfigFile();

		if (file_exists($filename)) {
			$config = sly_Util_YAML::load($filename);
			$this->localConfig = new sly_Util_Array($config);
			$this->cache = null;
		}
	}

	public function loadProjectConfig() {
		$filename = $this->getProjectConfigFile();

		if (file_exists($filename)) {
			$config = sly_Util_YAML::load($filename);
			$this->projectConfig = new sly_Util_Array($config);
			$this->cache = null;
		}
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  int     $mode      the mode in which the file should be loaded
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return mixed              false when an error occured, else the loaded configuration (most likely an array)
	 */
	protected function loadInternal($filename, $mode, $force = false, $key = '/') {
		if ($mode != self::STORE_LOCAL_DEFAULT && $mode != self::STORE_STATIC && $mode != self::STORE_PROJECT_DEFAULT) {
			throw new sly_Exception('Konfigurationsdateien können nur mit STORE_STATIC, STORE_LOCAL_DEFAULT oder STORE_PROJECT_DEFAULT geladen werden.');
		}

		if (empty($filename) || !is_string($filename)) throw new sly_Exception('Keine Konfigurationsdatei angegeben.');
		if (!file_exists($filename)) throw new sly_Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');

		$isStatic = $mode == self::STORE_STATIC;

		// force gibt es nur bei STORE_*_DEFAULT
		$force = $force && !$isStatic;

		// prüfen ob konfiguration in diesem request bereits geladen wurde
		if (!$force && isset($this->loadedConfigFiles[$filename])) {
			// statisch geladene konfigurationsdaten werden innerhalb des requests nicht mehr überschrieben
			if ($isStatic) {
				trigger_error('Statische Konfigurationsdatei '.$filename.' wurde bereits in einer anderen Version geladen! Daten wurden nicht überschrieben.', E_USER_WARNING);
			}
			return false;
		}

		$config = sly_Util_YAML::load($filename);

		// geladene konfiguration in globale konfiguration mergen
		$this->setInternal($key, $config, $mode, $force);

		$this->loadedConfigFiles[$filename] = true;

		return $config;
	}

	/**
	 * @param  string $key      the key to load
	 * @param  mixed  $default  value to return when $key was not found
	 * @return mixed            the found value or $default
	 */
	public function get($key, $default = null) {
		if ($this->cache === null) {
			// build merged config cache
			$this->cache = array_replace_recursive($this->staticConfig->get('/', array()), $this->localConfig->get('/', array()), $this->projectConfig->get('/', array()));
			$this->cache = new sly_Util_Array($this->cache);
		}

		return $this->cache->get($key, $default);
	}

	/**
	 * @param  string $key  the key to check
	 * @return boolean      true if found, else false
	 */
	public function has($key) {
		return $this->projectConfig->has($key) || $this->localConfig->has($key) || $this->staticConfig->has($key);
	}

	/**
	 * @param string $key  the key to remove
	 */
	public function remove($key) {
		$this->localConfig->remove($key);
		$this->localConfigModified = true;
		$this->projectConfig->remove($key);
		$this->projectConfigModified = true;
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return mixed          the set value or false if an error occured
	 */
	public function setStatic($key, $value) {
		return $this->setInternal($key, $value, self::STORE_STATIC);
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return mixed          the set value or false if an error occured
	 */
	public function setLocal($key, $value) {
		return $this->setInternal($key, $value, self::STORE_LOCAL);
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  boolean $force  force reloading the config or not
	 * @return mixed           the set value or false if an error occured
	 */
	public function setLocalDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_LOCAL_DEFAULT, $force);
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  boolean $force  force reloading the config or not
	 * @return mixed           the set value or false if an error occured
	 */
	public function setProjectDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_PROJECT_DEFAULT, $force);
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @param  int    $mode   one of the classes MODE constants
	 * @return mixed          the set value or false if an error occured
	 */
	public function set($key, $value, $mode = self::STORE_PROJECT) {
		return $this->setInternal($key, $value, $mode);
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  int     $mode   one of the classes MODE constants
	 * @param  boolean $force  force reloading the config or not
	 * @return mixed           the set value or false if an error occured
	 */
	protected function setInternal($key, $value, $mode, $force = false) {
		if (is_null($key) || strlen($key) === 0) {
			throw new sly_Exception('Key '.$key.' ist nicht erlaubt!');
		}

		$this->cache = null;

		if (!empty($value) && sly_Util_Array::isAssoc($value)) {
			foreach ($value as $ikey => $val) {
				$currentPath = trim($key.'/'.$ikey, '/');
				$this->setInternal($currentPath, $val, $mode, $force);
			}
			return $value;
		}

		if (empty($mode)) $mode = self::STORE_PROJECT;

		$this->setMode($key, $mode);

		if ($mode == self::STORE_STATIC) {
			return $this->staticConfig->set($key, $value);
		}

		if ($mode == self::STORE_LOCAL) {
			$this->localConfigModified = true;
			return $this->localConfig->set($key, $value);
		}

		if ($mode == self::STORE_LOCAL_DEFAULT) {
			if ($force || !$this->localConfig->has($key)) {
				$this->localConfigModified = true;
				return $this->localConfig->set($key, $value);
			}
			return false;
		}

		if ($mode == self::STORE_PROJECT_DEFAULT) {
			if ($force || !$this->projectConfig->has($key)) {
				$this->projectConfigModified = true;
				return $this->projectConfig->set($key, $value);
			}
			return false;
		}

		// case: sly_Configuration::STORE_PROJECT
		$this->projectConfigModified = true;
		return $this->projectConfig->set($key, $value);
	}

	/**
	 * @throws sly_Exception  if the mode is wrong
	 * @param  string $key    the key to set the mode of
	 * @param  int    $mode   one of the classes MODE constants
	 */
	protected function setMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		if ($this->checkMode($key, $mode)) return;
		if (isset($this->mode[$key])) {
			throw new sly_Exception('Mode für '.$key.' wurde bereits auf '.$this->mode[$key].' gesetzt.');
		}

		$this->mode[$key] = $mode;
	}

	/**
	 * @param  string $key  the key to look for
	 * @return mixed        the found mode (int) or null if the key does not exist
	 */
	protected function getMode($key) {
		if (!isset($this->mode[$key])) return null;
		return $this->mode[$key];
	}

	/**
	 * @param  string $key   the key to look for
	 * @param  int    $mode  one of the classes MODE constants
	 * @return boolean       if the mode matches or the key has no mode yet
	 */
	protected function checkMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		return !isset($this->mode[$key]) || $this->mode[$key] == $mode;
	}

	protected function flush() {
		if ($this->localConfigModified) {
			sly_Util_YAML::dump($this->getLocalConfigFile(), $this->localConfig->get(null));
		}

		if ($this->projectConfigModified) {
			sly_Util_YAML::dump($this->getProjectConfigFile(), $this->projectConfig->get(null));
		}
	}
}
