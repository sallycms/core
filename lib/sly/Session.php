<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Session {
	protected $installID; ///< string

	public function __construct($installID) {
		$this->installID = $installID;
	}

	public function getID() {
		return session_id();
	}

	public function getInstallID() {
		return $this->installID;
	}

	/**
	 * Get the value of a session var casted to $type.
	 *
	 * @param  string $key      the key where to find the var in superglobal aray $_SESSION
	 * @param  string $type     the type to cast to
	 * @param  mixed  $default  the default value to return if session var is not set
	 * @return mixed            $value casted to $type
	 */
	public function get($key, $type, $default = null) {
		$id = $this->installID;
		return (isset($_SESSION) && array_key_exists($id, $_SESSION)) ? sly_setarraytype($_SESSION[$id], $key, $type, $default) : $default;
	}

	/**
	 * Set the value of a session var
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value) {
		$_SESSION[$this->installID][$key] = $value;
	}

	/**
	 * Unsets a session var
	 *
	 * @param string $key
	 */
	public function delete($key) {
		unset($_SESSION[$this->installID][$key]);
	}

	/**
	 * Remove all data from the session
	 */
	public function flush() {
		unset($_SESSION[$this->installID]);
	}

	/**
	 * Remove all data, destroy and close session
	 */
	public function destroy() {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
		session_destroy();
		$_SESSION = array();
		session_write_close();
	}

	/**
	 * Create a new session ID
	 *
	 * Use this to prevent session fixation attacks. Call it after every
	 * authenticaten or authorisation change (login, logout).
	 */
	public function regenerateID() {
		session_regenerate_id(true);
	}
}
