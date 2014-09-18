<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Subset of a complete translation database
 *
 * This class wraps a regular sly_I18N object together with a specific prefix,
 * so that callers can omit the prefix. This is used in the mediapool so that
 * the common prefix of all strings ('pool_') does not have to be repeated over
 * and over again.
 *
 * Since most controllers just wrap the i18n calls, this class will probably
 * removed in a later release.
 *
 * @ingroup i18n
 * @author  Christoph
 * @since   0.3
 */
class sly_I18N_Subset implements sly_I18N_Base {
	/**
	 * Constructor
	 *
	 * Creates the wrapper object. Note that the contents of the wrapped i18n
	 * container is not copied, the object is just referenced.
	 *
	 * @param sly_I18N_Base $i18nContainer  the base container
	 * @param string        $prefix         the prefix to prepend
	 */
	public function __construct(sly_I18N_Base $i18nContainer, $prefix) {
		$this->container = $i18nContainer;
		$this->prefix    = $prefix;
	}

	/**
	 * Translate a key
	 *
	 * @param  string $key  the key to translate
	 * @return string       the translated message
	 */
	public function msg($key) {
		return $this->container->msg($this->prefix.$key);
	}

	/**
	 * Check for a message
	 *
	 * @param  string $key  the key to find
	 * @return boolean      true if the key exists, else false
	 */
	public function hasMsg($key) {
		return $this->container->hasMsg($this->prefix.$key);
	}
}
