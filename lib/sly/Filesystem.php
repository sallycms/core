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
 * @ingroup core
 * @since 0.9
 */
interface sly_Filesystem {
	/**
	 * get size of file in bytes
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return int                       filesize in byte
	 */
	public function getSize($fileName);

	/**
	 * get modification time of file as unix timestamp
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return int                       unix timestamp of file modification time
	 */
	public function getMtime($fileName);

	/**
	 * get public url of file
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return string                    public file url
	 */
	public function getUrl($fileName);

	// work

	/**
	 * checks if a file exists
	 *
	 * @param  string $fileName  filename
	 * @return boolean           true if file exists in this filesystem
	 */
	public function exists($fileName);

	/**
	 * get content of the file
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return string                    content of the file
	 */
	public function read($fileName);

	/**
	 * create file with content or overwrite file content
	 *
	 * @throws sly_Filesystem_Exception  if the file could not be written
	 * @param  string $fileName          filename
	 * @param  string $content           the raw file contents
	 */
	public function write($fileName, $content);

	/**
	 * set modification time of file to current time
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist or could not be modified
	 * @param  string $fileName          filename
	 */
	public function touch($fileName);

	/**
	 * remove file
	 *
	 * @throws sly_Filesystem_Exception  if the file could not be removed
	 * @param  string $fileName          filename
	 */
	public function remove($fileName);

	/**
	 * copies a file
	 *
	 * @param string $fromFileName
	 * @param string $destinationFileName
	 */
	public function copy($fromFileName, $destinationFileName);

	/**
	 * moves a file
	 *
	 * @param string $fromFileName
	 * @param string $destinationFileName
	 */
	public function move($fromFileName, $destinationFileName);

	/**
	 * get a list of files, filename starts with prefix
	 *
	 * @param  string $prefix
	 * @return array           plain list of files
	 */
	public function listFiles($prefix = '');
}
