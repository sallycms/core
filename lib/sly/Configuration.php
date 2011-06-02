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
 * @ingroup core
 */
class sly_Configuration {

	const STORE_PROJECT         = 1;
	const STORE_LOCAL           = 2;
	const STORE_LOCAL_DEFAULT   = 3;
	const STORE_STATIC          = 4;
	const STORE_PROJECT_DEFAULT = 5;

	private $mode              = array();
	private $loadedConfigFiles = array();

	private $staticConfig;
	private $localConfig;
	private $projectConfig;

	private $localConfigModified   = false;
	private $projectConfigModified = false;

	private static $instance;

	private function __construct() {
		$this->staticConfig  = new sly_Util_Array();
		$this->localConfig   = new sly_Util_Array();
		$this->projectConfig = new sly_Util_Array();
	}

	/**
	 * @return sly_Configuration
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();

		return self::$instance;
	}

	protected function getConfigDir() {
		$dir = SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'config';

		if (!sly_Util_Directory::createHttpProtected($dir)) {
			throw new sly_Exception('Config-Verzeichnis '.$dir.' konnte nicht erzeugt werden.');
		}

		return $dir;
	}

	protected function getLocalConfigFile() {
		return $this->getConfigDir().DIRECTORY_SEPARATOR.'sly_local.yml';
	}

	public function getProjectConfigFile() {
		return $this->getConfigDir().DIRECTORY_SEPARATOR.'sly_project.yml';
	}

	public function loadDevelop() {
		$dir = new sly_Util_Directory(SLY_BASE.DIRECTORY_SEPARATOR.'develop'.DIRECTORY_SEPARATOR.'config');
		if($dir->exists()) {
			foreach($dir->listPlain() as $file) {
				$this->loadStatic($dir.DIRECTORY_SEPARATOR.$file);
			}
		}
	}

	public function loadProject($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	public function loadStatic($filename, $key = '/') {
		return $this->loadInternal($filename, self::STORE_STATIC, false, $key);
	}

	public function loadLocalDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_LOCAL_DEFAULT, $force, $key);
	}

	public function loadProjectDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	public function loadLocalConfig(){
		$filename = $this->getLocalConfigFile();
		if(file_exists($filename)) {
			$config = sly_Util_YAML::load($filename);
			$this->localConfig = new sly_Util_Array($config);

		}
	}

	public function loadProjectConfig(){
		$filename = $this->getProjectConfigFile();
		if(file_exists($filename)) {
			$config = sly_Util_YAML::load($filename);
			$this->projectConfig = new sly_Util_Array($config);
		}
	}

	protected function loadInternal($filename, $mode, $force = false, $key = '/') {
		if ($mode != self::STORE_LOCAL_DEFAULT && $mode != self::STORE_STATIC && $mode != self::STORE_PROJECT_DEFAULT) {
			throw new Exception('Konfigurationsdateien können nur mit STORE_STATIC, STORE_LOCAL_DEFAULT oder STORE_PROJECT_DEFAULT geladen werden.');
		}
		if (empty($filename) || !is_string($filename)) throw new Exception('Keine Konfigurationsdatei angegeben.');
		if (!file_exists($filename)) throw new Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');

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

	public function get($key, $default = null) {
		if (!$this->has($key)) return $default;

		$p = $this->projectConfig->get($key, array());
		if (!is_array($p)) return $p;

		$l = $this->localConfig->get($key, array());
		if (!is_array($l)) return $l;

		$s = $this->staticConfig->get($key, array());
		if (!is_array($s)) return $s;

		return array_replace_recursive($s, $l, $p);
	}

	public function has($key) {
		return $this->projectConfig->has($key) || $this->localConfig->has($key) || $this->staticConfig->has($key);
	}

	public function remove($key) {
		$this->localConfig->remove($key);
		$this->projectConfig->remove($key);
	}

	public function setStatic($key, $value) {
		return $this->setInternal($key, $value, self::STORE_STATIC);
	}

	public function setLocal($key, $value) {
		return $this->setInternal($key, $value, self::STORE_LOCAL);
	}

	public function setLocalDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_LOCAL_DEFAULT, $force);
	}

	public function setProjectDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_PROJECT_DEFAULT, $force);
	}

	public function set($key, $value, $mode = self::STORE_PROJECT) {
		return $this->setInternal($key, $value, $mode);
	}

	protected function setInternal($key, $value, $mode, $force = false) {
		if (is_null($key) || strlen($key) === 0) {
			throw new sly_Exception('Key '.$key.' ist nicht erlaubt!');
		}
		if (is_array($value) && !empty($value) && sly_Util_Array::isAssoc($value)) {
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

	protected function setMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		if ($this->checkMode($key, $mode)) return;
		if (isset($this->mode[$key])) {
			throw new Exception('Mode für '.$key.' wurde bereits auf '.$this->mode[$key].' gesetzt.');
		}
		$this->mode[$key] = $mode;
	}

	protected function getMode($key) {
		if (!isset($this->mode[$key])) return null;
		return $this->mode[$key];
	}

	protected function checkMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		return !isset($this->mode[$key]) || $this->mode[$key] == $mode;
	}

	protected function flush() {
		if($this->localConfigModified) {
			sly_Util_YAML::dump($this->getLocalConfigFile(), $this->localConfig->get(null));
		}
		if($this->projectConfigModified) {
			sly_Util_YAML::dump($this->getProjectConfigFile(), $this->projectConfig->get(null));
		}
	}

	public function __destruct() {
		$this->flush();
	}
}
