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
	 * @param sly_I18N_Base $i18nContainer
	 * @param string        $prefix
	 */
	public function __construct(sly_I18N_Base $i18nContainer, $prefix) {
		$this->container = $i18nContainer;
		$this->prefix    = $prefix;
	}

	/**
	 * @param  string $prefix
	 * @return sly_I18N_Subset
	 */
	public static function create($prefix) {
		$i18n = sly_Core::getI18N();
		return new self($i18n, $prefix);
	}

	public function msg($key)          { return $this->container->msg($this->prefix.$key);          }
	public function addMsg($key, $msg) { return $this->container->addMsg($this->prefix.$key, $msg); }
	public function hasMsg($key)       { return $this->container->hasMsg($this->prefix.$key);       }
}
