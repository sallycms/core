<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Util_Csrf {
	const TOKEN_NAME = 'sly-csrf-token';

	private static function getSession(sly_Session $session = null) {
		return $session ? $session : sly_Core::getSession();
	}

	/**
	 * Read the CSRF token from the session
	 *
	 * @param  sly_Session $session  a concrete session or null to use the current one
	 * @return string                the token or null if it was not yet set
	 */
	public static function getToken(sly_Session $session = null) {
		return self::getSession($session)->get(self::TOKEN_NAME, 'string');
	}

	/**
	 * Set the CSRF token in the session
	 *
	 * @param string      $token    a pregenerated token or null to create one
	 * @param sly_Session $session  a concrete session or null to use the current one
	 */
	public static function setToken($token = null, sly_Session $session = null) {
		if (!is_string($token) || empty($token)) {
			$token = sly_Util_Password::getRandomData(40, true);
		}

		self::getSession($session)->set(self::TOKEN_NAME, $token);
	}

	/**
	 * Check if a valid token was submitted
	 *
	 * @throws sly_Exception                in case the session does not contain a token
	 * @throws sly_Authorisation_Exception  in case the token is invalid and $throwUp is true
	 *
	 * @param  string      $token    a token from whatever source or null to get the token from POST data
	 * @param  sly_Session $session  a concrete session or null to use the current one
	 * @param  boolean     $throwUp  true throws an exception on invalid tokens, false returns a boolean
	 * @param  sly_Request $request  the request to use or null for the global one
	 * @return boolean               true if the token was valid, else invalid
	 */
	public static function checkToken($token = null, sly_Session $session = null, $throwUp = true, sly_Request $request = null) {
		$ref = self::getToken($session);

		if ($ref === null) {
			throw new sly_Exception('Cannot check CSRF token because it has not yet been set.');
		}

		if (!is_string($token)) {
			$request = $request ? $request : sly_Core::getRequest();
			$token   = $request->post(self::TOKEN_NAME, 'string', null);
		}

		$ok = sly_Util_Password::equals($ref, $token);

		if (!$ok && $throwUp) {
			throw new sly_Authorisation_Exception('The submitted CSRF token does not match.');
		}

		return $ok;
	}

	public static function prepareForm(sly_Form $form, sly_Session $session = null) {
		if ($form->getMethod() === 'GET') {
			throw new sly_Exception('Cannot attach a CSRF token to a form that is submitted via GET.');
		}

		$token = self::getToken($session);

		if ($token === null) {
			throw new sly_Exception('Cannot set CSRF token because it has not yet been defined in the session.');
		}

		$form->addHiddenValue(self::TOKEN_NAME, $token);
	}

	public static function renderInputTag(sly_Session $session = null) {
		$token = self::getToken($session);

		if ($token === null) {
			throw new sly_Exception('Cannot render CSRF token because it has not yet been defined in the session.');
		}

		return sprintf('<input type="hidden" name="%s" value="%s" />', self::TOKEN_NAME, sly_html($token));
	}
}
