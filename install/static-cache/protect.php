<?php

// get client encoding

$enc = 'plain';

if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
	if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) $enc = 'gzip';
	elseif (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false) $enc = 'deflate';
}

// check file

$file = isset($_GET['file']) ? $_GET['file'] : '';
define('FILE', $file);
define('ENC', $enc);

$realfile = realpath('protected/'.$enc.'/'.$file);   // path or false
$index    = dirname(realpath(__FILE__));             // /var/www/home/cust/sally/data/dyn/public/sally/static-cache/

// append '/' if missing
if ($index[strlen($index)-1] === DIRECTORY_SEPARATOR) {
	$index .= DIRECTORY_SEPARATOR;
}

// file not found?
if ($realfile === false) {
	header('HTTP/1.0 404 Not Found');
	die;
}

// file outside of cache dir?
if (substr($realfile, 0, strlen($index)) !== $index) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

// jump to sally root directory
chdir('___JUMPER___');

// include project specific access rules
ob_start();
$allowAccess = false;
include 'develop/checkpermission.php';
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
$pos   = strrpos($file, '.');
$ext   = strtolower($pos === false ? $file : substr($file, $pos + 1));
$types = array(
	'css'  => 'text/css',
	'js'   => 'text/javascript',
	'ico'  => 'image/x-icon',
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png'  => 'image/png',
	'gif'  => 'image/gif',
	'swf'  => 'application/x-shockwave-flash'
);

if (!isset($mime)) {
	$mime = (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream').'; charset=UTF-8';
}

header('Content-Type: '.$mime);

if (ENC !== 'plain') {
	header('Content-Encoding: '.ENC);
}

readfile(FILE);
