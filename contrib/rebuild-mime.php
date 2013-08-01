<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

if (PHP_SAPI !== 'cli') {
	die('This script has to be run from command line.');
}

if (count($argv) < 2) {
	// try some common paths to find Apache 2

	$paths = array(
		'C:/xamp/apache/conf',
		'C:/xampp/apache/conf',
		'C:/xampplite/apache/conf',
		'/etc'
	);

	foreach ($paths as $test) {
		if (is_file($test.'/mime.types')) {
			define('TYPE_FILE', $test.'/mime.types');
			break;
		}
	}

	if (!defined('TYPE_FILE')) {
		print "Could not auto-locate your mime.types. Please give the full path yourself:\n";
		print "Usage: php $argv[0] /path/to/mime.types";
		die;
	}
}
else {
	$file = $argv[1];

	if (!is_file($file)) {
		die('Could not locate your mime.types.');
	}

	define('TYPE_FILE', $file);
}

chdir('..');
define('OUTPUT_FILE', 'config/mimetypes.yml');

require 'vendor/autoload.php';

$data = array();
$in   = fopen(TYPE_FILE, 'r');

while (!feof($in)) {
	$line = trim(fgets($in, 255));
	if (empty($line) || $line[0] === '#') continue;
	list($mime, $exts) = array_values(array_filter(explode("\t", $line)));

	$exts = explode(' ', $exts);

	foreach ($exts as $ext) {
		$data[$ext] = $mime;
	}
}

// make sure .less is defined
if (!isset($data['less'])) {
	$data['less'] = 'text/css';
}

ksort($data);

$yaml = sfYaml::dump($data, 2);
file_put_contents(OUTPUT_FILE, '# built date: '.date('r')."\n$yaml");

print 'Done.';
