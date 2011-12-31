<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_Cache {
	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function set($namespace, $key, $value) {
		return sly_Core::cache()->set($namespace, $key, $value);
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function get($namespace, $key, $default = null) {
		return sly_Core::cache()->get($namespace, $key, $default);
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @return boolean
	 */
	public static function delete($namespace, $key) {
		return sly_Core::cache()->delete($namespace, $key);
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @return boolean
	 */
	public static function exists($namespace, $key) {
		return sly_Core::cache()->exists($namespace, $key);
	}

	/**
	 * @param  string $namespace
	 * @return boolean
	 */
	public static function flush($namespace) {
		return sly_Core::cache()->flush($namespace, true);
	}

	/**
	 * @return boolean
	 */
	public static function registerListener() {
		if (!file_exists(self::getConfigFile())) {
			return false;
		}

		$config     = self::getConfig();
		$listener   = array(__CLASS__, 'listener');
		$dispatcher = sly_Core::dispatcher();
		$registered = array('SLY_CACHE_CLEARED');

		$dispatcher->register('SLY_CACHE_CLEARED', $listener);

		foreach ($config as $namespace => $events) {
			$events = sly_makeArray($events);
			$todo   = array_diff($events, $registered);

			foreach ($todo as $event) {
				$dispatcher->register($event, $listener);
			}

			$registered = array_merge($registered, $todo);
		}

		return true;
	}

	/**
	 * @param  array $params
	 * @return mixed
	 */
	public static function listener(array $params) {
		$config = self::getConfig();
		$event  = $params['event'];

		foreach ($config as $namespace => $events) {
			if ($event === 'SLY_CACHE_CLEARED' || in_array($event, $events)) {
				self::flush($namespace);
			}
		}

		return $params['subject'];
	}

	/**
	 * @return string
	 */
	public static function getConfigFile() {
		return SLY_DEVELOPFOLDER.'/config/caches.yml';
	}

	/**
	 * @return array
	 */
	public static function getConfig() {
		return sly_Util_YAML::load(self::getConfigFile());
	}
}
