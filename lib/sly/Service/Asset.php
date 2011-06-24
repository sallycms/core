<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
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

	const CACHE_DIR = 'public/sally/static-cache';
	const TEMP_DIR  = 'internal/sally/temp';

	const EVENT_PROCESS_ASSET        = 'SLY_CACHE_PROCESS_ASSET';
	const EVENT_REVALIDATE_ASSETS    = 'SLY_CACHE_REVALIDATE_ASSETS';
	const EVENT_GET_PROTECTED_ASSETS = 'SLY_CACHE_GET_PROTECTED_ASSETS';
	const EVENT_IS_PROTECTED_ASSET   = 'SLY_CACHE_IS_PROTECTED_ASSET';

	const ACCESS_PUBLIC    = 'public';
	const ACCESS_PROTECTED = 'protected';

	private $forceGen = true;

	public function __construct() {
		$this->initCache();

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->register(self::EVENT_PROCESS_ASSET, array($this, 'processScaffold'));
		$dispatcher->register('ALL_GENERATED', array(__CLASS__, 'clearCache'));
	}

	public function setForceGeneration($force = true) {
		$this->forceGen = (boolean) $force;
	}

	private function getPreferredClientEncoding() {
		static $enc;

		if (!isset($enc)) {
			$enc = false;
			$e   = trim(sly_get('encoding', 'string'), '/');

			if (in_array($e, array('plain', 'gzip', 'deflate'))) {
				$enc = $e;
			}
			elseif (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
				if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) $enc = 'gzip';
				elseif (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false) $enc = 'deflate';
			}
		}

		return $enc;
	}

	private function getPreferredCacheDir() {
		$enc = $this->getPreferredClientEncoding();
		return $enc === false ? 'plain' : $enc;
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

	public function process() {
		$file = sly_get('sly_asset', 'string');
		if (empty($file)) {
			if (isset($_GET['sly_asset'])) {
				header('HTTP/1.0 400 Bad Request');
				die();
			}
			return;
		}

		while (ob_get_level()) ob_end_clean();
		ob_start();

		// only process allowed files
		if (sly_Util_String::endsWith($file, '.php') || sly_Util_String::endsWith($file, 'htaccess')) {
			header('HTTP/1.0 403 Forbidden');
			die;
		}

		$dispatcher  = sly_Core::dispatcher();
		$isProtected = $dispatcher->filter(self::EVENT_IS_PROTECTED_ASSET, false, compact('file'));
		$access      = $isProtected ? self::ACCESS_PROTECTED : self::ACCESS_PUBLIC;

		// "/../data/dyn/public/sally/static-cache/[access]/gzip/assets/css/main.css"
		$cacheFile = $this->getCacheFile($file, $access);

		if (!file_exists($cacheFile) || $this->forceGen) {
			// lete listeners process the file
			$tmpFile = $dispatcher->filter(self::EVENT_PROCESS_ASSET, $file);

			// now we can check if a listener has generated a valid file
			if (!file_exists($tmpFile)) {
				header('HTTP/1.0 404 Not Found');
				die;
			}

			$this->generateCacheFile($tmpFile, $cacheFile);
		}

		$this->printCacheFile($cacheFile);
	}

	protected function getCacheDir($access = self::ACCESS_PUBLIC, $encoding = null) {
		$encoding = $encoding === null ? $this->getPreferredCacheDir() : $encoding;
		return sly_Util_Directory::join(SLY_DYNFOLDER, self::CACHE_DIR, $access, $encoding);
	}

	protected function getCacheFile($file = null, $access = self::ACCESS_PUBLIC, $encoding = null) {
		return sly_Util_Directory::join($this->getCacheDir($access, $encoding), $file);
	}

	protected function generateCacheFile($file, $cacheFile) {
		if (!is_dir(dirname($cacheFile))) mkdir(dirname($cacheFile), 0777, true);

		$enc = $this->getPreferredClientEncoding();

		switch ($enc) {
			case 'gzip':
				$out = gzopen($cacheFile, 'wb');
				$in  = fopen($file, 'rb');

				while (!feof($in)) {
		 			$buf = fread($in, 4096);
		 			gzwrite($out, $buf);
				}

				fclose($in);
				gzclose($out);
				break;

			case 'deflate':
				$out = fopen($cacheFile, 'wb');
				$in  = fopen($file, 'rb');

				stream_filter_append($out, 'zlib.deflate', STREAM_FILTER_WRITE, 6);

				while (!feof($in)) {
					fwrite($out, fread($in, 4096));
				}

				fclose($in);
				fclose($out);
				break;

			case 'plain':
			default:
				copy($file, $cacheFile);
		}
	}

	protected function printCacheFile($file) {
		$errors = ob_get_clean();

		if (empty($errors)) {
			// redirect so that Apache can set content-type and various other headers
			header('Location: '.$_SERVER['REQUEST_URI']);
		}
		else {
			header('Content-Type: text/plain; charset=UTF-8');
			header('HTTP/1.0 500 Internal Server Error');
			print $errors;
		}

		die;
	}

	public function processScaffold($params) {
		$file = $params['subject'];

		if (sly_Util_String::endsWith($file, '.css')) {
			$css = sly_Util_Scaffold::process($file);

			$tmpFile = sly_Util_Directory::join(SLY_DYNFOLDER, self::TEMP_DIR, md5($css).'.css');
			if (!file_exists(dirname($tmpFile))) mkdir(dirname($tmpFile), 0777, true);
			file_put_contents($tmpFile, $css);

			return $tmpFile;
		}

		return $file;
	}

	private function initCache() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, self::CACHE_DIR);
		if (!is_dir($dir)) mkdir($dir, 0777, true);

		$install  = SLY_COREFOLDER.'/install/static-cache/';
		$htaccess = sly_Util_Directory::join($dir, '.htaccess');

		if (!file_exists($htaccess)) {
			copy($install.'.htaccess', $htaccess);
		}

		$cache_php = sly_Util_Directory::join($dir, 'cache.php');

		if (!file_exists($cache_php)) {
			$jumper = self::getJumper($dir);
			file_put_contents($cache_php, "<?php chdir('$jumper'); include 'index.php';");
		}

		$protect_php = sly_Util_Directory::join($dir, 'protect.php');

		if (!file_exists($protect_php)) {
			$jumper   = self::getJumper($dir);
			$contents = file_get_contents($install.'protect.php');
			$contents = str_replace('___JUMPER___', $jumper, $contents);

			file_put_contents($protect_php, $contents);
		}

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			sly_Util_Directory::create($dir.'/'.$access.'/gzip',    0777);
			sly_Util_Directory::create($dir.'/'.$access.'/deflate', 0777);
			sly_Util_Directory::create($dir.'/'.$access.'/plain',   0777);

			if (!file_exists($dir.'/'.$access.'/gzip/.htaccess'))    copy($install.'gzip.htaccess',    $dir.'/'.$access.'/gzip/.htaccess');
			if (!file_exists($dir.'/'.$access.'/deflate/.htaccess')) copy($install.'deflate.htaccess', $dir.'/'.$access.'/deflate/.htaccess');
		}

		sly_Util_Directory::createHttpProtected($dir.'/'.self::ACCESS_PROTECTED);
	}

	private static function getJumper($dir) {
		static $jumper = null;

		if ($jumper === null) {
			$pathDiff = trim(substr($dir, strlen(SLY_BASE)), DIRECTORY_SEPARATOR);
			$jumper   = str_repeat('../', substr_count($pathDiff, DIRECTORY_SEPARATOR)+1);
		}

		return $jumper;
	}

	public static function clearCache(array $params) {
		$me  = new self();
		$dir = $me->getCacheDir('', '');

		// Remember htaccess files, so that we do not kill and re-create them.
		// This is important for servers which have custom rules (like RewriteBase settings).
		$htaccess['root'] = file_get_contents($dir.'/.htaccess');

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			$htaccess[$access.'_gzip']    = file_get_contents($dir.'/'.$access.'/gzip/.htaccess');
			$htaccess[$access.'_deflate'] = file_get_contents($dir.'/'.$access.'/deflate/.htaccess');
		}

		// clear the directory
		rex_deleteDir($dir, true);

		// re-init the cache dir
		$me->initCache();

		// restore the original .htaccess files again
		$htaccess['root'] = file_put_contents($dir.'/.htaccess', $htaccess['root']);

		foreach (array(self::ACCESS_PUBLIC, self::ACCESS_PROTECTED) as $access) {
			file_put_contents($dir.'/'.$access.'/gzip/.htaccess', $htaccess[$access.'_gzip']);
			file_put_contents($dir.'/'.$access.'/deflate/.htaccess', $htaccess[$access.'_deflate']);
		}

		return isset($params['subject']) ? $params['subject'] : true;
	}
}

