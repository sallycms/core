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
 * @author Dave
 */
class sly_Service_Asset {
	const CACHE_DIR = 'public/sally/static-cache';  ///< string
	const TEMP_DIR  = 'internal/sally/temp';        ///< string

	const EVENT_PROCESS_ASSET        = 'SLY_CACHE_PROCESS_ASSET';        ///< string
	const EVENT_REVALIDATE_ASSETS    = 'SLY_CACHE_REVALIDATE_ASSETS';    ///< string
	const EVENT_GET_PROTECTED_ASSETS = 'SLY_CACHE_GET_PROTECTED_ASSETS'; ///< string
	const EVENT_IS_PROTECTED_ASSET   = 'SLY_CACHE_IS_PROTECTED_ASSET';   ///< string

	const ACCESS_PUBLIC    = 'public';     ///< string
	const ACCESS_PROTECTED = 'protected';  ///< string

	private $forceGen = true; ///< boolean

	public function __construct() {
		$this->initCache();

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->register(self::EVENT_PROCESS_ASSET, array($this, 'processScaffold'), array(), true);
	}

	/**
	 * @param boolean $force
	 */
	public function setForceGeneration($force = true) {
		$this->forceGen = (boolean) $force;
	}

	public function validateCache() {
		$dispatcher = sly_Core::dispatcher();

		// [assets/css/main.css, data/mediapool/foo.jpg, ...] (echte, vorhandene Dateien)
		$protected = $dispatcher->filter(self::EVENT_GET_PROTECTED_ASSETS, array());
		$protected = array_unique(array_filter($protected));

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			foreach (array('plain', 'gzip', 'deflate') as $encoding) {
				$dir  = new sly_Util_Directory(realpath($this->getCacheDir($access, $encoding)));
				$list = $dir->listRecursive();

				// just in case ... should never happen because of $this->initCache()
				if ($list === false) continue;

				foreach ($list as $file) {
					// "/../data/dyn/public/sally/static-cache/gzip/assets/css/main.css"
					$cacheFile = $this->getCacheFile($file, $access, $encoding);
					$realfile  = SLY_BASE.'/'.$file;

					if (!file_exists($cacheFile)) {
						continue;
					}

					// if original file is missing, ask listeners
					if (!file_exists($realfile)) {
						$translated = $dispatcher->filter(self::EVENT_REVALIDATE_ASSETS, array($file));
						$realfile   = SLY_BASE.'/'.reset($translated); // "there can only be one!" ... or at least we hope so
					}

					$relative = str_replace(SLY_BASE, '', $realfile);
					$relative = str_replace('\\', '/', $relative);
					$relative = trim($relative, '/');

					// delete cache file if original is missing or outdated
					if (!file_exists($realfile) || filemtime($cacheFile) < filemtime($realfile)) {
						unlink($cacheFile);
					}

					// delete file if it's protected but where in public or vice versa
					elseif (in_array($relative, $protected) !== ($access === self::ACCESS_PROTECTED)) {
						unlink($cacheFile);
					}
				}
			}
		}
	}

	protected function normalizePath($path) {
		$path = sly_Util_Directory::normalize($path);
		$path = str_replace('..', '', $path);
		$path = str_replace(DIRECTORY_SEPARATOR, '/', sly_Util_Directory::normalize($path));
		$path = str_replace('./', '/', $path);
		$path = str_replace(DIRECTORY_SEPARATOR, '/', sly_Util_Directory::normalize($path));

		if (empty($path)) {
			return '';
		}

		if ($path[0] === '/') {
			$path = substr($path, 1);
		}

		return $path;
	}

	public function process($file, $encoding) {
		// check if the file can be streamed
		$blocked = sly_Core::config()->get('BLOCKED_EXTENSIONS');
		$ok      = true;

		foreach ($blocked as $ext) {
			if (sly_Util_String::endsWith($file, $ext)) {
				$ok = false;
				break;
			}
		}

		if ($ok) {
			$normalized = $this->normalizePath($file);
			$ok         = strpos($normalized, '/') === false; // allow files in root directory (favicon)

			if (!$ok) {
				$allowed = sly_Core::config()->get('ASSETS_DIRECTORIES');

				foreach ($allowed as $path) {
					if (sly_Util_String::startsWith($file, $path)) {
						$ok = true;
						break;
					}
				}
			}
		}

		if (!$ok) {
			throw new sly_Authorisation_Exception('Forbidden');
		}

		// do the work

		$dispatcher  = sly_Core::dispatcher();
		$isProtected = $dispatcher->filter(self::EVENT_IS_PROTECTED_ASSET, false, compact('file'));
		$access      = $isProtected ? self::ACCESS_PROTECTED : self::ACCESS_PUBLIC;

		// "/../data/dyn/public/sally/static-cache/[access]/gzip/assets/css/main.css"
		$cacheFile = $this->getCacheFile($file, $access, $encoding);

		// if the file already exists, stop here (can only happen if someone
		// manually requests index.php?slycontroller...).
		if (file_exists($cacheFile) && !$this->forceGen) {
			return new sly_Response('', 400);
		}

		// let listeners process the file
		$tmpFile = $dispatcher->filter(self::EVENT_PROCESS_ASSET, $file);

		// now we can check if a listener has generated a valid file
		if (!is_file($tmpFile)) return null;

		// create the encoded file
		$this->generateCacheFile($tmpFile, $cacheFile, $encoding);

		if (!file_exists($cacheFile)) {
			return null;
		}

		// return the plain, unencoded file to the asset controller
		return $tmpFile;
	}

	public function removeCacheFiles($file) {
		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			foreach (array('plain', 'gzip', 'deflate') as $encoding) {
				// "/../data/dyn/public/sally/static-cache/gzip/assets/css/main.css"
				$cacheFile = $this->getCacheFile($file, $access, $encoding);

				if (!file_exists($cacheFile)) {
					continue;
				}

				unlink($cacheFile);
			}
		}
	}

	/**
	 * @param  string $access
	 * @param  string $encoding
	 * @return string
	 */
	protected function getCacheDir($access, $encoding) {
		return sly_Util_Directory::join(SLY_DYNFOLDER, self::CACHE_DIR, $access, $encoding);
	}

	/**
	 * @param  string $file
	 * @param  string $access
	 * @param  string $encoding
	 * @return string
	 */
	protected function getCacheFile($file, $access, $encoding) {
		return sly_Util_Directory::join($this->getCacheDir($access, $encoding), $file);
	}

	/**
	 * @param string $sourceFile
	 * @param string $cacheFile
	 */
	protected function generateCacheFile($sourceFile, $cacheFile, $encoding) {
		clearstatcache();
		sly_Util_Directory::create(dirname($cacheFile), $this->getDirPerm());

		$level = error_reporting(0);

		switch ($encoding) {
			case 'gzip':
				$out = gzopen($cacheFile, 'wb');
				$in  = fopen($sourceFile, 'rb');

				while (!feof($in)) {
		 			$buf = fread($in, 4096);
		 			gzwrite($out, $buf);
				}

				fclose($in);
				gzclose($out);
				break;

			case 'deflate':
				$out = fopen($cacheFile, 'wb');
				$in  = fopen($sourceFile, 'rb');

				stream_filter_append($out, 'zlib.deflate', STREAM_FILTER_WRITE, 6);

				while (!feof($in)) {
					fwrite($out, fread($in, 4096));
				}

				fclose($in);
				fclose($out);
				break;

			case 'plain':
			default:
				copy($sourceFile, $cacheFile);
		}

		if (file_exists($cacheFile)) {
			chmod($cacheFile, $this->getFilePerm());
		}

		error_reporting($level);
	}

	/**
	 * @param string $origFile
	 * @param string $file
	 */
	protected function printCacheFile($file) {
		$fp = @fopen($file, 'rb');

		if (!$fp) {
			$errors = 'Cannot open file.';
		}

		if (empty($errors)) {
			while (!feof($fp)) {
				print fread($fp, 65536);
			}

			fclose($fp);
		}
		else {
			throw new sly_Exception($errors);
		}
	}

	/**
	 * @param  array $params
	 * @return string
	 */
	public function processScaffold($params) {
		$file = $params['subject'];

		if (sly_Util_String::endsWith($file, '.css') && file_exists(SLY_BASE.'/'.$file)) {
			$css     = sly_Util_Scaffold::process($file);
			$dir     = SLY_DYNFOLDER.'/'.self::TEMP_DIR;
			$tmpFile = $dir.'/'.md5($file).'.css';

			sly_Util_Directory::create($dir, $this->getDirPerm());

			file_put_contents($tmpFile, $css);
			chmod($tmpFile, $this->getFilePerm());

			return $tmpFile;
		}

		return $file;
	}

	private function initCache() {
		$dirPerm  = $this->getDirPerm();
		$filePerm = $this->getFilePerm();
		$dir      = SLY_DYNFOLDER.'/'.self::CACHE_DIR;

		sly_Util_Directory::create($dir, $dirPerm);

		$install  = SLY_COREFOLDER.'/install/static-cache/';
		$htaccess = $dir.'/.htaccess';

		if (!file_exists($htaccess)) {
			copy($install.'.htaccess', $htaccess);
			chmod($htaccess, $filePerm);
		}

		$protect_php = $dir.'/protect.php';

		if (!file_exists($protect_php)) {
			$jumper   = self::getJumper($dir);
			$contents = file_get_contents($install.'protect.php');
			$contents = str_replace('___JUMPER___', $jumper, $contents);

			file_put_contents($protect_php, $contents);
			chmod($protect_php, $filePerm);
		}

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			sly_Util_Directory::create($dir.'/'.$access.'/gzip', $dirPerm);
			sly_Util_Directory::create($dir.'/'.$access.'/deflate', $dirPerm);
			sly_Util_Directory::create($dir.'/'.$access.'/plain', $dirPerm);

			$file = $dir.'/'.$access.'/gzip/.htaccess';

			if (!file_exists($file)) {
				copy($install.'gzip.htaccess', $file);
				chmod($file, $filePerm);
			}

			$file = $dir.'/'.$access.'/deflate/.htaccess';

			if (!file_exists($file)) {
				copy($install.'deflate.htaccess', $file);
				chmod($file, $filePerm);
			}
		}

		sly_Util_Directory::createHttpProtected($dir.'/'.self::ACCESS_PROTECTED);
	}

	/**
	 * @param  string $dir
	 * @return string
	 */
	private static function getJumper($dir) {
		static $jumper = null;

		if ($jumper === null) {
			$pathDiff = trim(substr($dir, strlen(SLY_BASE)), DIRECTORY_SEPARATOR);
			$jumper   = str_repeat('../', substr_count($pathDiff, DIRECTORY_SEPARATOR)+1);
		}

		return $jumper;
	}

	public static function clearCache() {
		$me  = new self();
		$dir = $me->getCacheDir('', '');

		// Remember htaccess files, so that we do not kill and re-create them.
		// This is important for servers which have custom rules (like RewriteBase settings).
		$htaccess['root'] = file_get_contents($dir.'/.htaccess');

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			$htaccess[$access.'_gzip']    = file_get_contents($dir.'/'.$access.'/gzip/.htaccess');
			$htaccess[$access.'_deflate'] = file_get_contents($dir.'/'.$access.'/deflate/.htaccess');
		}

		// remove the cache directory
		$obj = new sly_Util_Directory($dir);
		$obj->delete(true);

		// clear the Scaffold temp dir
		$scaffoldDir = sly_Util_Directory::join(SLY_DYNFOLDER, self::TEMP_DIR);
		$obj         = new sly_Util_Directory($scaffoldDir);

		$obj->deleteFiles(true);

		// re-init the cache dir
		$me->initCache();

		// restore the original .htaccess files again
		$htaccess['root'] = file_put_contents($dir.'/.htaccess', $htaccess['root']);

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			file_put_contents($dir.'/'.$access.'/gzip/.htaccess', $htaccess[$access.'_gzip']);
			file_put_contents($dir.'/'.$access.'/deflate/.htaccess', $htaccess[$access.'_deflate']);
		}
	}

	private function getFilePerm() {
		return sly_Core::getFilePerm();
	}

	private function getDirPerm() {
		return sly_Core::getDirPerm();
	}
}
