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
 * I18N interface
 *
 * @ingroup i18n
 * @author  Christoph
 * @since   0.3
 */
interface sly_I18N_Base {
	/**
	 * Translate a key
	 *
	 * @param  string $key
	 * @return string
	 */
	public function msg($key);

	/**
	 * Add a new message to the translated database at runtime
	 *
	 * @param string $key
	 * @param string $msg
	 */
	public function addMsg($key, $msg);

	/**
	 * Check if a message exists
	 *
	 * @param  string $key
	 * @return boolean
	 */
	public function hasMsg($key);
}
