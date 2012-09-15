<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Composer {
	protected $filename;
	protected $package;
	protected $proxy;
	protected $mtime;
	protected $data;

	const EXTRA_SUBKEY = 'sallycms';

	/**
	 * constructor
	 *
	 * @param string $filename
	 */
	public function __construct($filename) {
		$this->filename = $filename;
		$this->proxy    = null;
		$this->mtime    = null;
		$this->data     = false;
	}

	/**
	 * set the current package name
	 *
	 * @param  string $package
	 * @return sly_Util_Composer  self
	 */
	public function setPackage($package) {
		$this->package = $package;
		return $this;
	}

	/**
	 * get current composer.json filename
	 *
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * read composer.json
	 *
	 * @param  string $composerDir
	 * @return array
	 */
	public function getContent($composerDir = null) {
		if ($this->data === false) {
			$found = false;

			if (($composerDir || $this->proxy) && $this->package) {
				$composerDir = $this->proxy ? dirname($this->proxy) : $composerDir;

				foreach (array('installed.json', 'installed_dev.json') as $filename) {
					$filename = $composerDir.'/'.$filename;

					if (file_exists($filename)) {
						$data = sly_Util_JSON::load($filename, false, true);

						foreach ($data as $package) {
							if (isset($package['name']) && $package['name'] === $this->package) {
								$this->data  = $package;
								$this->mtime = filemtime($filename);
								$this->proxy = $filename;

								$found = true;
								break 2;
							}
						}
					}
				}
			}

			if (!$found && file_exists($this->filename)) {
				$this->data  = sly_Util_JSON::load($this->filename, false, true);
				$this->mtime = filemtime($this->filename);
				$this->proxy = null;
			}
			elseif (!$found) {
				throw new sly_Exception(t('file_not_found', $this->filename));
			}
		}

		return $this->data;
	}

	/**
	 * revalidate the internal cache
	 *
	 * @return boolean
	 */
	public function revalidate() {
		if ($this->mtime !== null) {
			$realFile = $this->proxy ? $this->proxy : $this->filename;

			if (!file_exists($realFile)) {
				$this->data  = null;
				$this->mtime = time();
				return true;
			}

			if (filemtime($realFile) > $this->mtime) {
				$this->data = false;
				$this->getContent();
				return true;
			}
		}

		return false;
	}

	/**
	 * @param  string  $key
	 * @param  boolean $tryExtra  if true $key is first searched in /extra/sallycms/$key, before /$key ist searched
	 * @return string
	 */
	public function getKey($key, $tryExtra = true) {
		if ($tryExtra) {
			$val = $this->getSallyKey($key);
			if ($val !== null) return $val;
		}

		$data = $this->getContent();
		return $data === null ? null : (array_key_exists($key, $data) ? $data[$key] : null);
	}

	/**
	 * @param  string $subkey
	 * @return string
	 */
	public function getSallyKey($subkey = null) {
		$extra = $this->getKey('extra', false);
		$key   = self::EXTRA_SUBKEY;

		// nothing set
		if (!isset($extra[$key])) return null;

		$extra = $extra[$key];

		// return everything if requested
		if ($subkey === null) return $extra;

		return array_key_exists($subkey, $extra) ? $extra[$subkey] : null;
	}
}
