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
	 * get size of file if byte
	 *
	 * @param  string                    $fileName
	 * @return int                       filesize in byte
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function getSize($fileName);

	/**
	 * get modification time of file as unix timestamp
	 *
	 * @param  string                    $fileName
	 * @return int                       unix timestamp of file modification time
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function getMtime($fileName);

	/**
	 * get public url of file
	 *
	 * @param  string                    $fileName
	 * @return string                    public file url
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function getUrl($fileName);

	// work

	/**
	 * checks if a file exists
	 *
	 * @param  type     $fileName
	 * @return boolean  true if file exists in this filesystem
	 */
	public function exists($fileName);

	/**
	 * get content of the file
	 *
	 * @param  string                    $fileName
	 * @return string                    content of the file
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function read($fileName);

	/**
	 * create file with content or overwrite file content
	 *
	 * @param  string $fileName
	 * @param  string $content
	 * @throws sly_Filesystem_Exception  if the file could not be written
	 */
	public function write($fileName, $content);

	/**
	 * set modification time of file to current time
	 *
	 * @param  string $fileName
	 * @throws sly_Filesystem_Exception  if the file does not exist or could not be modified
	 */
	public function touch($fileName);

	/**
	 * remove file
	 *
	 * @param  string $fileName
	 * @throws sly_Filesystem_Exception  if the file could not be removed
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
	 * @param string $prefix
	 */
	public function listFiles($prefix = '');
}