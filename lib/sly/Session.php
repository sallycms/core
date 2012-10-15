<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Session {
	protected $installID; ///< string

	const CSRF_TOKEN_NAME = 'sly-csrf-token';

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
		return array_key_exists($id, $_SESSION) ? sly_setarraytype($_SESSION[$id], $key, $type, $default) : $default;
	}

	/**
	 * Get the CSRF token
	 *
	 * @return string  the token or null if not set
	 */
	public function getCsrfToken() {
		return $this->get(self::CSRF_TOKEN_NAME, 'string');
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
	 * Set the CSRF token
	 *
	 * @param string $token  a pregenerated token or null to create one
	 */
	public function setCsrfToken($token = null) {
		if (!is_string($token) || empty($token)) {
			$token = sly_Util_Password::getRandomData(40, true);
		}

		$this->set(self::CSRF_TOKEN_NAME, $token);
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
	 * Remove all data from the session
	 */
	public function destroy() {
		session_destroy();
		$_SESSION = array();
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

	/**
	 * Check if a valid token was submitted
	 *
	 * @param string $token  a token from whatever source or null to get the token from POST data
	 */
	public function checkCsrfToken($token = null) {
		$ref = $this->getCsrfToken();

		if ($ref === null) {
			throw new sly_Exception('Cannot check CSRF token because it has not yet been set.');
		}

		if (!is_string($token)) {
			$token = sly_post(self::CSRF_TOKEN_NAME, 'string', null);
		}

		return sly_Util_Password::equals($ref, $token);
	}
}
