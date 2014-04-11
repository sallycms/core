<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\Filesystem;
use Gaufrette\Adapter;

/**
 * Custom filesystem
 */
class sly_Filesystem_Filesystem extends Filesystem implements sly_Filesystem_Interface {
	protected $domain;

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter
	 * @param string  $protocol
	 */
	public function __construct(Adapter $adapter, $domain = null) {
		parent::__construct($adapter);
		$this->domain = $domain;
	}

	/**
     * {@inheritDoc}
     */
	public function getDomain() {
		return $this->domain;
	}

	/**
     * {@inheritDoc}
     */
	public function getPath($key) {
		return $this->getDomain().'/'.$key;
	}

	/**
	 * Lists keys beginning with given prefix
	 * (no wildcard / regex matching)
	 *
	 * if adapter implements ListKeysAware interface, adapter's implementation will be used,
	 * in not, ALL keys will be requested and iterated through.
	 *
	 * This method contains the fix in hason's pull request at
	 * https://github.com/KnpLabs/Gaufrette/pull/170 for making the $prefix
	 * actually work.
	 *
	 * @param  string $prefix
	 * @return array
	 */
	public function listKeys($prefix = '') {
		$adapter = $this->getAdapter();

		if ($adapter instanceof ListKeysAware) {
			return $adapter->listKeys($prefix);
		}

		$dirs = array();
		$keys = array();

		foreach ($this->keys() as $key) {
			if (empty($prefix) || 0 === strpos($key, $prefix)) {
				if ($adapter->isDirectory($key)) {
					$dirs[] = $key;
				}
				else {
					$keys[] = $key;
				}
			}
		}

		return array(
			'keys' => $keys,
			'dirs' => $dirs
		);
	}
}
