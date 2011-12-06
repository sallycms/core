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
 * AddOn service
 *
 * This class implements the base service for addOns.
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_AddOn extends sly_Service_AddOn_Base {
	/**
	 * @param  string $addonName
	 * @return string
	 */
	public function baseFolder($addonName) {
		$dir = SLY_ADDONFOLDER.DIRECTORY_SEPARATOR;
		if (!empty($addonName)) $dir .= $addonName.DIRECTORY_SEPARATOR;
		return $dir;
	}

	/**
	 * @param  string $type
	 * @param  string $addonName
	 * @return string
	 */
	protected function dynFolder($type, $addonName) {
		$config = sly_Core::config();
		$dir    = SLY_DYNFOLDER.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	/**
	 * @param  string  $time
	 * @param  string  $type
	 * @param  string  $addonName
	 * @param  boolean $state
	 * @return mixed
	 */
	protected function extend($time, $type, $addonName, $state) {
		return sly_Core::dispatcher()->filter('SLY_ADDON_'.$time.'_'.$type, $state, array('addon' => $addonName));
	}

	/**
	 * Setzt eine Eigenschaft des AddOns.
	 *
	 * @param  string $addonName  Name des AddOns
	 * @param  string $property   Name der Eigenschaft
	 * @param  mixed  $value      Wert der Eigenschaft
	 * @return mixed              der gesetzte Wert
	 */
	public function setProperty($addonName, $property, $value) {
		return sly_Core::config()->set('ADDON/'.$addonName.'/'.$property, $value);
	}

	/**
	 * Gibt eine Eigenschaft des AddOns zurück.
	 *
	 * @param  string $addonName  Name des AddOns
	 * @param  string $property   Name der Eigenschaft
	 * @param  mixed  $default    Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return mixed              Wert der Eigenschaft des AddOns
	 */
	public function getProperty($addonName, $property, $default = null) {
		return sly_Core::config()->get('ADDON/'.$addonName.'/'.$property, $default);
	}

	/**
	 * Gibt ein Array aller registrierten AddOns zurück.
	 *
	 * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.yaml).
	 *
	 * @return array  Array aller registrierten AddOns
	 */
	public function getRegisteredAddons() {
		$data = sly_Core::config()->get('ADDON');
		$data = !empty($data) ? array_keys($data) : array();
		natsort($data);
		return $data;
	}

	/**
	 * Gibt ein Array von verfügbaren Addons zurück.
	 *
	 * Ein Addon ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @return array  Array der verfügbaren AddOns
	 */
	public function getAvailableAddons() {
		$avail = array();

		foreach ($this->getRegisteredAddons() as $addonName) {
			if ($this->isAvailable($addonName)) $avail[] = $addonName;
		}

		natsort($avail);
		return $avail;
	}

	/**
	 * @param  string $addonName
	 * @return null
	 */
	public function loadAddon($addonName) {
		return $this->load($addonName);
	}

	/**
	 * @return string
	 */
	protected function getI18NPrefix() {
		return 'addon_';
	}

	/**
	 * @param  string $addonName
	 * @return string
	 */
	protected function getVersionKey($addonName) {
		return 'addons/'.$addonName;
	}

	/**
	 * Returns the path in config object
	 *
	 * @param  string $addonName
	 * @return string             a path like "ADDON/x"
	 */
	protected function getConfPath($addonName) {
		return 'ADDON/'.$addonName;
	}

	public function exists($addon) {
		$base = $this->baseFolder($addon);
		if(!is_dir($base)) return false;
		if(!file_exists($base.'config.inc.php')) return false;
		return true;
	}
}
