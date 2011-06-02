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
 * @ingroup i18n
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
		global $I18N;
		return new self($I18N, $prefix);
	}

	public function msg($key)          { return $this->container->msg($this->prefix.$key);          }
	public function addMsg($key, $msg) { return $this->container->addMsg($this->prefix.$key, $msg); }
	public function hasMsg($key)       { return $this->container->hasMsg($this->prefix.$key);       }
}
