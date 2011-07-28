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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Plugin extends sly_Service_AddOn_Base {
	public function baseFolder($plugin) {
		if (!is_array($plugin)) {
			list($addon, $pluginName) = array($plugin, '');
		}
		else {
			list($addon, $pluginName) = $plugin;
		}

		$dir  = sly_Service_Factory::getAddOnService()->baseFolder($addon);
		$dir .= 'plugins'.DIRECTORY_SEPARATOR;

		if (!empty($pluginName)) {
			$dir .= $pluginName.DIRECTORY_SEPARATOR;
		}

		return $dir;
	}

	protected function dynFolder($type, $plugin) {
		list($addon, $pluginName) = $plugin;

		$s   = DIRECTORY_SEPARATOR;
		$dir = SLY_DYNFOLDER.$s.$type.$s.$addon.$s.$pluginName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function extend($time, $type, $plugin, $state) {
		list($addon, $pluginName) = $plugin;
		return sly_Core::dispatcher()->filter('SLY_PLUGIN_'.$time.'_'.$type, $state, array('addon' => $addon, 'plugin' => $pluginName));
	}

	/**
	 * Setzt eine Eigenschaft des Addons.
	 *
	 * @param  array  $plugin    Plugin als array(addon, plugin)
	 * @param  string $property  Name der Eigenschaft
	 * @param  mixed  $property  Wert der Eigenschaft
	 * @return mixed             der gesetzte Wert
	 */
	public function setProperty($plugin, $property, $value) {
		list($addon, $pluginName) = $plugin;
		return sly_Core::config()->set('ADDON/'.$addon.'/plugins/'.$pluginName.'/'.$property, $value);
	}

	/**
	 * Gibt eine Eigenschaft des Plugins zurück.
	 *
	 * @param  array  $plugin     Plugin als array(addon, plugin)
	 * @param  string $property   Name der Eigenschaft
	 * @param  mixed  $default    Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return string             Wert der Eigenschaft des Plugins
	 */
	public function getProperty($plugin, $property, $default = null) {
		list($addon, $pluginName) = $plugin;
		return sly_Core::config()->get('ADDON/'.$addon.'/plugins/'.$pluginName.'/'.$property, $default);
	}

	/**
	 * Gibt ein Array aller registrierten Plugins zurück.
	 *
	 * Ein Plugin ist registriert, wenn es dem System bekannt ist (plugins.yaml).
	 *
	 * @return array  Array aller registrierten Plugins
	 */
	public function getRegisteredPlugins($addon) {
		$plugins = array_keys(sly_Core::config()->get('ADDON/'.$addon.'/plugins', array()));
		natsort($plugins);
		return $plugins;
	}

	/**
	 * Gibt ein Array von verfügbaren Plugins zurück.
	 *
	 * Ein Plugin ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @return array  Array der verfügbaren Plugins
	 */
	public function getAvailablePlugins($addon) {
		$avail = array();

		foreach ($this->getRegisteredPlugins($addon) as $pluginName) {
			if ($this->isAvailable(array($addon, $pluginName))) {
				$avail[] = $pluginName;
			}
		}

		natsort($avail);
		return $avail;
	}

	/**
	 * Gibt ein Array aller installierten Plugins zurück.
	 *
	 * @param  string $addon  Name des AddOns
	 * @return array          Array aller registrierten Plugins
	 */
	public function getInstalledPlugins($addon) {
		$avail = array();

		foreach ($this->getRegisteredPlugins($addon) as $plugin) {
			if ($this->isInstalled(array($addon, $plugin))) $avail[] = $plugin;
		}

		natsort($avail);
		return $avail;
	}

	public function loadPlugin($plugin) {
		return $this->load($plugin);
	}

	protected function getI18NPrefix() {
		return 'plugin_';
	}

	protected function getVersionKey($plugin) {
		return 'plugins/'.implode('_', $plugin);
	}

	/**
	 * Returns the path in config object
	 *
	 * @param  array $plugin  the plugin
	 * @return string         a path like "ADDON/x/plugins/y"
	 */
	protected function getConfPath($plugin) {
		list($addon, $name) = $plugin;
		return 'ADDON/'.$addon.'/plugins/'.$name;
	}
}
