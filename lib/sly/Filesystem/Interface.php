<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_Filesystem_Interface {

	/**
	 * Get the protocol this filesystem is registered under
	 *
	 * @return string  a string like 'media' (without '://')
	 */
	public function getProtocol();

	public function has($key);

	public function rename($sourceKey, $targetKey);

	public function get($key, $create = false);

	public function write($key, $content, $overwrite = false);

	public function read($key);

	public function delete($key);

	public function keys();

	public function listKeys($prefix = '');

	/**
	 * Returns the last modified time of the specified file
	 *
	 * @param  string  $key
	 * @return integer An UNIX like timestamp
	 */
	public function mtime($key);

	/**
	 * Returns the checksum of the specified file's content
	 *
	 * @param  string $key
	 * @return string a hash
	 */
	public function checksum($key);

	/**
	 * Returns the size of the specified file's content
	 *
	 * @param  string  $key
	 * @return integer file size in byte
	 */
	public function size($key);

	public function createStream($key);

	public function createFile($key);
}
