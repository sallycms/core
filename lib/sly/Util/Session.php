<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Session {
	/**
	 * Start a session if it is not already started
	 */
	public static function start($onlyIfCookieSet = false, $sessionID = null) {
		/*
		Do NOT use session_id() here, because it could give you the wrong info.
		Normally, in an ideal world, session_id() would be fine and we're all happy.
		But when using FullPageCache, there (maybe) has already a session been
		started and "closed" (session_write_close). In this particular case, a
		call to session_id() would return the current session ID not no session
		would be active.
		To work around this limitation, we check for $_SESSION. This var will be
		explicitely unset() by FullPageCache.
		*/

		if (!$onlyIfCookieSet || self::isCookieSet() || $sessionID !== null) {
			if (!isset($_SESSION) || !session_id()) {
				// force httponly flag but leave other stuff unchanged
				$params = session_get_cookie_params();
				session_set_cookie_params($params['lifetime'], $params['path'], $params['domain'], $params['secure'], true);

				if ($sessionID !== null) {
					session_id($sessionID);
				}

				session_start();

				return true;
			}
		}

		return false;
	}

	/**
	 * Get the value of a session var casted to $type.
	 *
	 * @param  string $key      the key where to find the var in superglobal aray $_SESSION
	 * @param  string $type     the type to cast to
	 * @param  mixed  $default  the default value to return if session var is not set
	 * @return mixed            $value casted to $type
	 */
	public static function get($key, $type = '', $default = null) {
		return sly_Core::getSession()->get($key, $type, $default);
	}

	/**
	 * Set the value of a session var
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public static function set($key, $value) {
		return sly_Core::getSession()->set($key, $value);
	}

	/**
	 * Unset a session var
	 *
	 * @deprecated use delete() instead
	 *
	 * @param string $key
	 */
	public static function reset($key) {
		return sly_Core::getSession()->delete($key);
	}

	/**
	 * Delete a session var
	 *
	 * @param string $key
	 */
	public static function delete($key) {
		return sly_Core::getSession()->delete($key);
	}

	/**
	 * Prevent session fixation
	 */
	public static function regenerate_id() {
		return sly_Core::getSession()->regenerateID();
	}

	public static function isCookieSet() {
		return !empty($_COOKIE[session_name()]);
	}

	public static function destroy() {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
		session_destroy();
		session_write_close();
	}
}
