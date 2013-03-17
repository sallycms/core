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
	 * Constructor
	 *
	 * @throws sly_Filesystem_Exception
	 * @param  string $base              absolute path to the storage directory
	 */
	public function __construct($base) {
		$realpath = realpath($base);

		if ($realpath === false) {
			throw new sly_Filesystem_Exception('Base directory "'.$base.'" is not valid!');
		}

		$this->base = rtrim($realpath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
	}

	/**
	 * get size of file in byte
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return int                       filesize in byte
	 */
	public function getSize($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);

		return filesize($fileName);
	}

	/**
	 * get modification time of file as unix timestamp
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return int                       unix timestamp of file modification time
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
	 * @param  string $fileName  filename
	 * @return boolean           true if file exists in this filesystem
	 */
	public function exists($fileName) {
		$fileName = $this->getFullPath($fileName);

		return file_exists($fileName);
	}

	/**
	 * get content of the file
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist
	 * @param  string $fileName          filename
	 * @return string                    content of the file
	 */
	public function read($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);

		return file_get_contents($fileName);
	}

	/**
	 * create file with content or overwrite file content
	 *
	 * @throws sly_Filesystem_Exception  if the file could not be written
	 * @param  string $fileName          filename
	 * @param  string $content           raw file contents
	 */
	public function write($fileName, $content) {
		$fileName = $this->getFullPath($fileName);
		$this->createDirectoryForFilePath($fileName);

		if (@file_put_contents($fileName, $content) === false) {
			$this->throwException('File '.$fileName.' could not be written');
		}
	}

	/**
	 * set modification time of file to current time
	 *
	 * @throws sly_Filesystem_Exception  if the file does not exist or could not be modified
	 * @param  string $fileName          filename
	 */
	public function touch($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);

		if (@touch($fileName) === false) {
			$this->throwException('File '.$fileName.' could not be touched');
		}
	}

	/**
	 * remove file
	 *
	 * @throws sly_Filesystem_Exception  if the file could not be removed
	 * @param  string $fileName          filename
	 */
	public function remove($fileName) {
		$fileName = $this->getFullPath($fileName);
		$this->exceptIfNotExists($fileName);

		if (@unlink($fileName) === false) {
			$this->throwException('File '.$fileName.' could not be removed');
		}
	}

	/**
	 * copies a file
	 *
	 * @throws sly_Filesystem_Exception     if the source file was not found, or the destination could not be written
	 * @param  string $fileName
	 * @param  string $destinationFileName
	 */
	public function copy($fromFileName, $destinationFileName) {
		$fromFileName        = $this->getFullPath($fromFileName);
		$destinationFileName = $this->getFullPath($fileName);

		$this->exceptIfNotExists($fromFileName);
		$this->createDirectoryForFilePath($destinationFileName);

		if (@copy($fromFileName, $destinationFileName) === false) {
			$this->throwException('File '.$fromFileName.' could not be copied to '.$destinationFileName);
		}
	}

	/**
	 * moves a file
	 *
	 * @throws sly_Filesystem_Exception     if the source file was not found, or the destination could not be written
	 * @param  string $fromFileName
	 * @param  string $destinationFileName
	 */
	public function move($fromFileName, $destinationFileName) {
		$this->exceptIfNotExists($fromFileName);
		$destinationFileName = $this->getFullPath($destinationFileName);
		$this->createDirectoryForFilePath($destinationFileName);

		if (@rename($fromFileName, $destinationFileName) === false) {
			$this->throwException('File '.$fromFileName.' could not be moved to '.$destinationFileName);
		}
	}

	/**
	 * get a list of files, filename starts with prefix
	 *
	 * @param  string $prefix
	 * @return array
	 */
	public function listFiles($prefix = '') {
		return glob($this->base.$prefix.'*');
	}

	/**
	 * throw a exception if a file does not exist
	 *
	 * @throws sly_Filesystem_Exception
	 * @param  string $fileName
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

	protected function throwException($message) {
		$error = error_get_last();
		$error = isset($error['message']) ? $error['message'] : 'Unknown error';

		throw new sly_Filesystem_Exception($message.': '.$error);
	}
}
