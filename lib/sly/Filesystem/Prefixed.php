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
use Gaufrette\Util\Path;

/**
 * Prefixed Decorator
 *
 * This class extends Filesystem just because there is no interface in Gaufrette
 * and userland code should just rely on a Filesystem instance (aka this should
 * be transparent).
 * It's implemented as a wrapper to allow easier run-time wrapping of the
 * major filesystem instances by services.
 */
class sly_Filesystem_Prefixed extends Filesystem implements sly_Filesystem_Interface {
	protected $prefix;
	protected $fs;

	/**
	 * Constructor
	 *
	 * @param Gaufrette\Filesystem $fs      the actual filesystem to wrap
	 * @param string               $prefix  the prefix to prepend to all filenames
	 */
	public function __construct(Filesystem $fs, $prefix) {
		$this->fs     = $fs;
		$this->prefix = Path::normalize($prefix).'/';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAdapter() {
		return $this->fs->getAdapter();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getProtocol() {
		return $this->fs->getProtocol();
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($key) {
		return $this->fs->has($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function rename($sourceKey, $targetKey) {
		return $this->fs->rename($this->prefix.Path::normalize($sourceKey), $this->prefix.Path::normalize($targetKey));
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key, $create = false) {
		return $this->fs->get($this->prefix.Path::normalize($key), $create);
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($key, $content, $overwrite = false) {
		return $this->fs->write($this->prefix.Path::normalize($key), $content, $overwrite);
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($key) {
		return $this->fs->read($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($key) {
		return $this->fs->delete($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function keys() {
		return $this->cleanKeyList($this->fs->keys());
	}

	/**
	 * {@inheritdoc}
	 */
	public function listKeys($prefix = '') {
		$data = $this->fs->listKeys($this->prefix.Path::normalize($prefix));

		return array(
			'keys' => $this->cleanKeyList($data['keys']),
			'dirs' => $this->cleanKeyList($data['dirs'])
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function mtime($key) {
		return $this->fs->mtime($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function checksum($key) {
		return $this->fs->checksum($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function createStream($key) {
		return $this->fs->createStream($this->prefix.Path::normalize($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function createFile($key) {
		return $this->fs->createFile($this->prefix.Path::normalize($key));
	}

	/**
	 * Clean a list of keys
	 *
	 * This methods strips the prefix from all keys and only add those to the
	 * result that are actually inside the prefix.
	 *
	 * @param  array $files
	 * @return array
	 */
	protected function cleanKeyList(array $files) {
		$result = array();
		$start  = mb_strlen($this->prefix);

		foreach ($files as $file) {
			$rel = mb_substr($file, $start);

			if (mb_strlen($rel) > 0) {
				$result[] = $rel;
			}
		}

		return $result;
	}
}
