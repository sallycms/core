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

class sly_Filesystem_Service {
	protected $fs;

	/**
	 * Constructor
	 *
	 * @param Gaufrette\Filesystem $fs  the filesystem to work with
	 */
	public function __construct(Filesystem $fs) {
		$this->fs = $fs;
	}

	/**
	 * Create a new Service instance
	 *
	 * @param  Gaufrette\Filesystem $fs
	 * @return sly_Filesystem_Service
	 */
	public static function create(Filesystem $fs) {
		return new self($fs);
	}

	/**
	 * Delete multiple files
	 *
	 * @param  array $filenames
	 * @return sly_Filesystem_Service
	 */
	public function deleteMultiple(array $filenames) {
		foreach ($filenames as $filename) {
			$this->fs->delete($filename);
		}

		return $this;
	}

	/**
	 * Delete all files
	 *
	 * @return sly_Filesystem_Service
	 */
	public function deleteAllFiles() {
		return $this->deleteFiles('', true);
	}

	/**
	 * Delete all files in a directory
	 *
	 * @param  string  $prefix
	 * @param  boolean $recursive
	 * @return sly_Filesystem_Service
	 */
	public function removeFiles($prefix, $recursive) {
		$filenames = $this->fs->listKeys($prefix);
		$prefix    = $prefix === '' ? '' : Path::normalize($prefix);

		foreach ($filenames as $filename) {
			if ($recursive) {
				$this->fs->delete($filename);
			}
			else {
				$dirname = dirname($filename);
				$dirname = '.' === $dirname ? '' : $dirname;

				if ($prefix === $dirname) {
					$this->fs->delete($filename);
				}
			}
		}

		return $this;
	}

	public function importFile($sourceFile, $targetFile) {
		$in  = fopen($sourceFile, 'rb');
		$out = $this->fs->createStream($targetFile);

		$out->open(new Gaufrette\StreamMode('wb'));

		while (!feof($in)) {
			$out->write(fread($in, 16384));
		}

		fclose($in);
		$out->close();
	}

	/**
	 * Mirrors part of the filesystem into another filesystem
	 *
	 * This will remove all the files in the target directory path and then copy
	 * all source files over.
	 *
	 * @param  string     $prefix        local prefix (set to '' to copy whole filesystem)
	 * @param  Filesystem $targetFs      the filesystem to write to
	 * @param  string     $targetPrefix  the prefix to use in the target filesystem
	 * @return Service
	 */
	public function mirrorTo($prefix, Filesystem $targetFs, $targetPrefix) {
		$filenames    = $this->fs->listKeys($prefix);
		$isRoot       = $prefix === '';
		$prefix       = $isRoot ? '' : Path::normalize($prefix);
		$targetPrefix = $targetPrefix === '' ? '' : Path::normalize($targetPrefix);

		// wipe the target directory
		$targetService = new self($targetFs);
		$targetService->deleteFiles($targetPrefix, true);

		// copy the source files
		foreach ($filenames as $filename) {
			// $filename is absolute, but we want to append the relative path to its $prefix.
			$relName    = $isRoot ? $filename : mb_substr($filename, mb_strlen($prefix)+1);
			$targetName = $targetPrefix.'/'.$relName;

			$targetFs->write($targetName, $this->fs->read($filename));
		}

		return $this;
	}
}
