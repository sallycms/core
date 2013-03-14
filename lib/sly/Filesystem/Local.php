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
 * @since   0.9
 */
abstract class sly_Filesystem_Local implements sly_Filesystem {
	protected $base;

	/**
	 *
	 * @param  string $base
	 * @throws sly_Filesystem_Exception
	 */
	public function __construct($base) {
		$base = sly_Util_Directory::normalize($base);
		if ($base === false) {
			throw new sly_Filesystem_Exception('Base directory is not valid!');
		}
		$this->base = $base.DIRECTORY_SEPARATOR;
	}

	/**
	 * get size of file if byte
	 *
	 * @param  string                    $fileName
	 * @return int                       filesize in byte
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function getSize($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);
		return filesize($fileName);
	}

	/**
	 * get modification time of file as unix timestamp
	 *
	 * @param  string                    $fileName
	 * @return int                       unix timestamp of file modification time
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function getMtime($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);
		return filemtime($fileName);
	}

	// work

	/**
	 * checks if a file exists
	 *
	 * @param  type     $fileName
	 * @return boolean  true if file exists in this filesystem
	 */
	public function exists($fileName) {
		$fileName = $this->getFullPath($fileName);
		return file_exists($fileName);
	}

	/**
	 * get content of the file
	 *
	 * @param  string                    $fileName
	 * @return string                    content of the file
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 */
	public function read($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);
		return file_get_contents($fileName);
	}

	/**
	 * create file with content or overwrite file content
	 *
	 * @param  string $fileName
	 * @param  string $content
	 * @throws sly_Filesystem_Exception  if the file could not be written
	 */
	public function write($fileName, $content) {
		$fileName = $this->getFullPath($fileName);
		$this->createDirectoryForFilePath($fileName);
		$success = file_put_contents($fileName, $content);

		if (!$success) {
			throw new sly_Filesystem_Exception('File '.$fileName.' could not be written!');
		}
	}

	/**
	 * set modification time of file to current time
	 *
	 * @param  string $fileName
	 * @throws sly_Filesystem_Exception  if the file does not exist or could not be modified
	 */
	public function touch($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);
		$success = touch($fileName);

		if (!$success) {
			throw new sly_Filesystem_Exception('File '.$fileName.' could not be touched!');
		}
	}

	/**
	 * remove file
	 *
	 * @param  string $fileName
	 * @throws sly_Filesystem_Exception  if the file could not be removed
	 */
	public function remove($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);

		$success = unlink($fileName);

		if (!$success) {
			throw new sly_Filesystem_Exception('File '.$fileName.' could not be removed!');
		}
	}

	/**
	 * copies a file
	 *
	 * @param string $fromFileName
	 * @param string $destinationFileName
	 * @throws sly_Filesystem_Exception  if the source file was not found, or the destination could not be written
	 */
	public function copy($fromFileName, $destinationFileName) {
		$fromFileName        = $this->getFullPath($fromFileName);
		$destinationFileName = $this->getFullPath($fileName);

		$this->exceptIfNotExists($fromFileName);
		$this->createDirectoryForFilePath($destinationFileName);

		$success = copy($fromFileName, $destinationFileName);

		if (!$success) {
			throw new sly_Filesystem_Exception('File '.$destinationFileName.' could not be written!');
		}
	}

	/**
	 * moves a file
	 *
	 * @param string $fromFileName
	 * @param string $destinationFileName
	 * @throws sly_Filesystem_Exception  if the source file was not found, or the destination could not be written
	 */
	public function move($fromFileName, $destinationFileName) {
		$fileName = $this->getFullPath($fileName);
		
		$this->exceptIfNotExists($fromFileName);
		$this->createDirectoryForFilePath($destinationFileName);

		$success = rename($fromFileName, $destinationFileName);

		if (!$success) {
			throw new sly_Filesystem_Exception('File '.$destinationFileName.' could not be written!');
		}
	}

	/**
	 * get a list of files, filename starts with prefix
	 *
	 * @param string $prefix
	 */
	public function listFiles($prefix = '') {
		return glob($this->base.$prefix.'*');
	}

	/**
	 * throw a exception if a file does not exist
	 *
	 * @param type $fileName
	 * @throws sly_Filesystem_Exception
	 */
	protected function exceptIfNotExists($fileName) {
		if (!$this->exists($fileName)) {
			throw new sly_Filesystem_Exception('File '.$fileName.' does not exist!');
		}
	}

	protected function getFullPath($fileName) {
		return $this->base.trim($fileName, DIRECTORY_SEPARATOR);
	}

	protected function createDirectoryForFilePath($fileName) {
		sly_Util_Directory::create(dirname($fileName));
	}
}