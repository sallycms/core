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
	const STORE_PROJECT_DEFAULT = 2; ///< int
	const STORE_LOCAL           = 3; ///< int
	const STORE_LOCAL_DEFAULT   = 4; ///< int
	const STORE_STATIC          = 5; ///< int

	private $mode;                  ///< array
	private $loadedConfigFiles;     ///< array
	private $staticConfig;          ///< sly_Util_Array
	private $localConfig;           ///< sly_Util_Array
	private $projectConfig;         ///< sly_Util_Array
	private $cache;                 ///< sly_Util_Array
	private $fileService;           ///< sly_Service_File_Base
	private $flush;                 ///< boolean
	private $localConfigModified;   ///< boolean
	private $projectConfigModified; ///< boolean

	/**
	 * Create a new instance. Having more than one instance with
	 * flush enabled can be very dangerous. Take care.
	 *
	 * @param sly_Service_File_Base $fileService
	 */
	public function __construct(sly_Service_File_Base $fileService) {
		$this->mode                  = array();
		$this->loadedConfigFiles     = array();
		$this->staticConfig          = new sly_Util_Array();
		$this->localConfig           = new sly_Util_Array();
		$this->projectConfig         = new sly_Util_Array();
		$this->fileService           = $fileService;
		$this->cache                 = null;
		$this->flush                 = true;
		$this->localConfigModified   = false;
		$this->projectConfigModified = false;
	}

	public function __destruct() {
		if ($this->flush) {
			$this->flush();
		}
	}

	/**
	 * activate/deactivate writing of configuration
	 *
	 * @param boolean $enabled
	 */
	public function setFlushOnDestruct($enabled) {
		$this->flush = (boolean) $enabled;
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

	/**
	 * @throws sly_Exception     when something is fucked up (file not found, bad parameters, ...)
	 * @param  string $filename  the file to load
	 * @param  string $key       where to mount the loaded config
	 * @return boolean           false when an error occured, true if everything went fine
	 */
	public function loadStatic($filename, $key = '/') {
		return $this->loadInternal($filename, self::STORE_STATIC, false, $key);
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return boolean            false when an error occured, true if everything went fine
	 */
	public function loadLocalDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_LOCAL_DEFAULT, $force, $key);
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return boolean            false when an error occured, true if everything went fine
	 */
	public function loadProjectDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	/**
	 * loads all YAML files in SLY_DEVELOPFOLDER./config to STATIC facility
	 */
	public function loadDevelopConfig() {
		$dir = new sly_Util_Directory(SLY_DEVELOPFOLDER.DIRECTORY_SEPARATOR.'config');

		if ($dir->exists()) {
			foreach ($dir->listPlain() as $file) {
				if (fnmatch('*.yml', $file) || fnmatch('*.yaml', $file)) {
					$this->loadStatic($dir.DIRECTORY_SEPARATOR.$file);
				}
			}
		}
	}

	/**
	 * load the sally local config
	 *
	 * @return boolean  false when an error occured, true if everything went fin
	 */
	public function loadLocalConfig() {
		$filename = $this->getLocalConfigFile();

		//do not hickup if the file does not exist
		if (file_exists($filename)) {
			return $this->loadInternal($filename, self::STORE_LOCAL);
		}
		return false;
	}

	/**
	 * load the sally project config
	 *
	 * @return boolean  false when an error occured, true if everything went fin
	 */
	public function loadProjectConfig() {
		$filename = $this->getProjectConfigFile();

		//do not hickup if the file does not exist
		if (file_exists($filename)) {
			return $this->loadInternal($filename, self::STORE_PROJECT);
		}
		return false;
	}

	/**
	 * @throws sly_Exception      when something is fucked up (file not found, bad parameters, ...)
	 * @param  string  $filename  the file to load
	 * @param  int     $mode      the mode in which the file should be loaded
	 * @param  boolean $force     force reloading the config or not
	 * @param  string  $key       where to mount the loaded config
	 * @return boolean            false when an error occured, true if everything went fine
	 */
	protected function loadInternal($filename, $mode, $force = false, $key = '/') {
		if (empty($filename) || !is_string($filename)) throw new sly_Exception('Keine Konfigurationsdatei angegeben.');

		$isStatic = $mode == self::STORE_STATIC;

		// force gibt es nur bei STORE_*_DEFAULT
		$force = $force && !$isStatic;

		// prüfen ob konfiguration in diesem request bereits geladen wurde
		if (!$force && isset($this->loadedConfigFiles[$filename])) {
			// geladene konfigurationsdaten werden innerhalb des requests nicht mehr überschrieben
			trigger_error('Konfigurationsdatei '.$filename.' wurde bereits in einer anderen Version geladen! Daten wurden nicht überschrieben.', E_USER_WARNING);
			return false;
		}

		$config = $this->fileService->load($filename, false, true);

		//do not try to merge empty files
		if (empty($config)) {
			trigger_error('Konfigurationsdatei '.$filename.' ist leer.', E_USER_WARNING);
			return false;
		}
		// geladene konfiguration in globale konfiguration mergen
		$this->setInternal($key, $config, $mode, $force);

		$this->loadedConfigFiles[$filename] = true;

		return true;
	}

	/**
	 * @param  string $key      the key to load
	 * @param  mixed  $default  value to return when $key was not found
	 * @return mixed            the found value or $default
	 */
	public function get($key, $default = null) {
		$this->warmUp();
		return $this->cache->get($key, $default);
	}

	/**
	 * @param  string $key  the key to check
	 * @return boolean      true if found, else false
	 */
	public function has($key) {
		$this->warmUp();
		return $this->cache->has($key);
	}

	/**
	 * @param string $key  the key to remove
	 */
	public function remove($key) {
		$this->localConfigModified   = $this->localConfig->remove($key);;
		$this->projectConfigModified = $this->projectConfig->remove($key);
		$this->cache = null;
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return boolean        false when an error occured, true if everything went fine
	 */
	public function setStatic($key, $value) {
		return $this->setInternal($key, $value, self::STORE_STATIC);
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @return boolean        false when an error occured, true if everything went fine
	 */
	public function setLocal($key, $value) {
		return $this->setInternal($key, $value, self::STORE_LOCAL);
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  boolean $force  force reloading the config or not
	 * @return boolean         false when an error occured, true if everything went fine
	 */
	public function setLocalDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_LOCAL_DEFAULT, $force);
	}

	/**
	 * @throws sly_Exception   if the key is invalid or has the wrong mode
	 * @param  string  $key    the key to set the value to
	 * @param  mixed   $value  the new value
	 * @param  boolean $force  force reloading the config or not
	 * @return boolean         false when an error occured, true if everything went fine
	 */
	public function setProjectDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_PROJECT_DEFAULT, $force);
	}

	/**
	 * @throws sly_Exception  if the key is invalid or has the wrong mode
	 * @param  string $key    the key to set the value to
	 * @param  mixed  $value  the new value
	 * @param  int    $mode   one of the classes MODE constants
	 * @return boolean        false when an error occured, true if everything went fine
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
	 * @return boolean         false when an error occured, true if everything went fine
	 */
	protected function setInternal($key, $value, $mode, $force = false) {
		if (is_null($key) || strlen($key) === 0) {
			throw new sly_Exception('Key '.$key.' ist nicht erlaubt!');
		}

		if (!empty($value) && sly_Util_Array::isAssoc($value)) {
			$key = trim($key, '/');

			foreach ($value as $ikey => $val) {
				$currentPath = $key.'/'.$ikey;
				$this->setInternal($currentPath, $val, $mode, $force);
			}

			return $value;
		}

		$mode = $this->getStoreMode($key, $mode, $force);

		$this->mode[$key] = $mode;
		$this->cache      = null;

		switch ($mode) {
			case self::STORE_STATIC:
				$this->staticConfig->set($key, $value);
				break;

			case self::STORE_LOCAL:
				$this->localConfigModified = true;
				$this->localConfig->set($key, $value);
				break;

			case self::STORE_PROJECT:
				$this->projectConfigModified = true;
				$this->projectConfig->set($key, $value);
		}

		return true;
	}

	/**
	 * @throws sly_Exception  if the mode is wrong
	 * @param  string  $key   the key to set the mode of
	 * @param  int     $mode  one of the classes MODE constants
	 * @return int            one of the classes MODE constants
	 */
	protected function getStoreMode($key, $mode, $force) {
		// handle default facilities
		if ($mode === self::STORE_LOCAL_DEFAULT || $mode === self::STORE_PROJECT_DEFAULT) {
			$mode--; // move to real facility

			// if the key does not exists or else it is in our real facility and we force override
			if (!isset($this->mode[$key]) || ($force && $this->mode[$key] === $mode)) {
				return $mode;
			}

			throw new sly_Exception('Can not load defaults for '.$key.'. The value is already set.');
		}
		else {
			// for all others allow duplicate setting of a key only in a higher level facility
			if (isset($this->mode[$key]) && $this->mode[$key] < $mode) {
				throw new sly_Exception('Mode for '.$key.' is already set to '.$this->mode[$key].'.');
			}
		}

		return $mode;
	}

	/**
	 * write the local and projectconfiguration to disc
	 */
	protected function flush() {
		if ($this->localConfigModified) {
			$this->fileService->dump($this->getLocalConfigFile(), $this->localConfig->get(null));
		}

		if ($this->projectConfigModified) {
			$this->fileService->dump($this->getProjectConfigFile(), $this->projectConfig->get(null));
		}
	}

	/**
	 * warm up the internal cache for get/has operations
	 */
	protected function warmUp() {
		if ($this->cache === null) {
			// build merged config cache
			$this->cache = array_replace_recursive($this->staticConfig->get('/', array()), $this->localConfig->get('/', array()), $this->projectConfig->get('/', array()));
			$this->cache = new sly_Util_Array($this->cache);
		}
	}
}
