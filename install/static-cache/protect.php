<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

if (PHP_SAPI === 'cli') {
	return;
}

$cachedir = isset($_SERVER['HTTP_ENCODING_CACHEDIR']) ? $_SERVER['HTTP_ENCODING_CACHEDIR'] : null;
$cachedir = is_null($cachedir) && isset($_SERVER['REDIRECT_HTTP_ENCODING_CACHEDIR']) ? $_SERVER['REDIRECT_HTTP_ENCODING_CACHEDIR'] : null;

// bad request?
if (!is_string($cachedir)) {
	header('HTTP/1.0 400 Bad Request');
	die;
}

if (!isset($_GET['file']) || !is_string($_GET['file'])) {
	header('HTTP/1.0 400 Bad Request');
	die;
}

// get client encoding (attention: use the one set by htaccess for mod_headers awareness)

$enc = trim($cachedir, '/');

// check file

$file = trim($_GET['file']);
define('FILE', $file);
define('ENC', $enc);

$realfile = realpath('protected/'.$enc.'/'.$file);   // path or false
$index    = dirname(realpath(__FILE__));             // /var/www/home/cust/data/dyn/public/sally/static-cache/

// file not found?
if ($realfile === false) {
	header('HTTP/1.0 404 Not Found');
	die;
}

// append '/' if missing
if ($index[mb_strlen($index)-1] === DIRECTORY_SEPARATOR) {
	$index .= DIRECTORY_SEPARATOR;
}

// file outside of cache dir?
if (mb_substr($realfile, 0, mb_strlen($index)) !== $index) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

// jump to sally root directory
chdir('___JUMPER___');

// include project specific access rules
ob_start();

$allowAccess = false;
$checkScript = 'develop/checkpermission.php';

if (file_exists($checkScript)) {
	include $checkScript;
}

$errors = ob_get_clean();

// jump back (at 88 mph)
chdir(dirname(__FILE__));

// disable sending the file when any kind of errors occured
if (!empty($errors)) {
	$allowAccess = false;
}

if (!$allowAccess) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

$file  = basename(FILE);
$pos   = mb_strrpos($file, '.');
$ext   = strtolower($pos === false ? $file : mb_substr($file, $pos + 1));
$types = array(
	'css'  => 'text/css',
	'less' => 'text/css',
	'js'   => 'text/javascript',
	'ico'  => 'image/x-icon',
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png'  => 'image/png',
	'gif'  => 'image/gif',
	'webp' => 'image/webp',
	'swf'  => 'application/x-shockwave-flash',
	'pdf'  => 'application/pdf'
);

if (!isset($mime)) {
	$mime = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';

	if (substr($mime, 0, 5) === 'text/' && strpos($mime, 'charset=') === false) {
		$mime .= '; charset=UTF-8';
	}
}

header('Content-Type: '.$mime);

// make sure intermediate servers don't cache the asset
header('Cache-Control: private');

if (ENC !== 'plain') {
	header('Content-Encoding: '.ENC);
}

readfile($realfile);
