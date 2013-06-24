<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Doctrine\DBAL\Schema\Table as Table;
use Doctrine\DBAL\Types\Type as Type;
use Doctrine\DBAL\Schema\Schema as Schema;
use Doctrine\DBAL\Platforms;

$baseDir = dirname(__DIR__);
require $baseDir.'/vendor/autoload.php';

////////////////////////////////////////////////////////////////////////////////
// detect Sally version

$json    = json_decode(file_get_contents($baseDir.'/composer.json'));
$version = explode('.', $json->version);
$version = sprintf('%d.%d.*', $version[0], $version[1]);

////////////////////////////////////////////////////////////////////////////////
// our schema

$schema = new Schema();

////////////////////////////////////////////////////////////////////////////////
// sly_article

$table = createTable($schema, 'sly_article');

$table->addColumn('id',         'integer')->setUnsigned(true);
$table->addColumn('clang',      'integer')->setUnsigned(true);
$table->addColumn('revision',   'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('online',     'boolean')->setDefault('0');
$table->addColumn('deleted',    'boolean')->setDefault('0');
$table->addColumn('type',       'string')->setLength(64);
$table->addColumn('re_id',      'integer')->setUnsigned(true);
$table->addColumn('path',       'string')->setLength(255);
$table->addColumn('pos',        'integer')->setUnsigned(true);
$table->addColumn('name',       'string')->setLength(255);
$table->addColumn('catpos',     'integer')->setUnsigned(true);
$table->addColumn('catname',    'string')->setLength(255);
$table->addColumn('startpage',  'boolean')->setDefault('0');
userCols($table);
$table->addColumn('attributes', 'text');

$table->setPrimaryKey(array('id', 'clang', 'revision'));
$table->addIndex(array('re_id'), 'parents');
$table->addIndex(array('type'), 'types');

////////////////////////////////////////////////////////////////////////////////
// sly_article_slice

$table = createTable($schema, 'sly_article_slice');

$table->addColumn('id',         'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('article_id', 'integer')->setUnsigned(true);
$table->addColumn('clang',      'integer')->setUnsigned(true);
$table->addColumn('revision',   'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('pos',        'integer')->setUnsigned(true);
$table->addColumn('slot',       'string')->setLength(64);
$table->addColumn('slice_id',   'integer')->setUnsigned(true);
userCols($table);

$table->setPrimaryKey(array('id'));
$table->addIndex(array('article_id', 'clang', 'revision'), 'find_article');

////////////////////////////////////////////////////////////////////////////////
// sly_clang

$table = createTable($schema, 'sly_clang');

$table->addColumn('id',       'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('revision', 'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('name',     'string')->setLength(255);
$table->addColumn('locale',   'string')->setLength(5);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_config

$table = createTable($schema, 'sly_config');

$table->addColumn('id',    'string')->setLength(255);
$table->addColumn('value', 'text');

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_file

$table = createTable($schema, 'sly_file');

$table->addColumn('id',           'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('revision',     'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('category_id',  'integer')->setUnsigned(true);
$table->addColumn('title',        'string')->setLength(255);
$table->addColumn('filename',     'string')->setLength(255);
$table->addColumn('originalname', 'string')->setLength(255);
$table->addColumn('filetype',     'string')->setLength(255);
$table->addColumn('filesize',     'integer')->setUnsigned(true);
$table->addColumn('width',        'integer')->setUnsigned(true);
$table->addColumn('height',       'integer')->setUnsigned(true);
userCols($table);
$table->addColumn('attributes',   'text')->setNotnull(false);

$table->setPrimaryKey(array('id'));
$table->addIndex(array('filename'), 'filename');

////////////////////////////////////////////////////////////////////////////////
// sly_file_category

$table = createTable($schema, 'sly_file_category');

$table->addColumn('id',         'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('revision',   'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('name',       'string')->setLength(255);
$table->addColumn('re_id',      'integer')->setUnsigned(true);
$table->addColumn('path',       'string')->setLength(255);
userCols($table);
$table->addColumn('attributes', 'text')->setNotnull(false);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_registry

$table = createTable($schema, 'sly_registry');

$table->addColumn('name',  'string')->setLength(255);
$table->addColumn('value', 'blob');

$table->setPrimaryKey(array('name'));

////////////////////////////////////////////////////////////////////////////////
// sly_slice

$table = createTable($schema, 'sly_slice');

$table->addColumn('id',                'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('module',            'string')->setLength(64);
$table->addColumn('serialized_values', 'text');

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_user

$table = createTable($schema, 'sly_user');

$table->addColumn('id',          'integer')->setUnsigned(true)->setAutoincrement(true);
$table->addColumn('revision',    'integer')->setUnsigned(true)->setDefault(0);
$table->addColumn('login',       'string')->setLength(128);
$table->addColumn('password',    'string')->setLength(128);
$table->addColumn('status',      'boolean')->setDefault('0');
$table->addColumn('name',        'string')->setLength(255)->setNotnull(false);
$table->addColumn('description', 'string')->setLength(255)->setNotnull(false);
$table->addColumn('timezone',    'string')->setLength(64)->setNotnull(false);
$table->addColumn('lasttrydate', 'datetime')->setNotnull(false);
userCols($table);
$table->addColumn('attributes',  'text')->setNotnull(false);

$table->setPrimaryKey(array('id'));
$table->addIndex(array('login'), 'logins');

////////////////////////////////////////////////////////////////////////////////
// create the actual SQL files

$platforms = array(
	'mysql'  => new Platforms\MySqlPlatform(),
	'sqlite' => new Platforms\SqlitePlatform(),
	'pgsql'  => new Platforms\PostgreSqlPlatform(),
	'oci'    => new Platforms\OraclePlatform()
);

$header = <<<HEADER
-- Sally Database Dump Version $version
-- Prefix sly_
HEADER;

$footer = <<<INSERT
-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
INSERT INTO sly_config (id, value) VALUES ('start_article_id', '1');
INSERT INTO sly_config (id, value) VALUES ('notfound_article_id', '1');
INSERT INTO sly_config (id, value) VALUES ('default_clang_id', '1');
INSERT INTO sly_config (id, value) VALUES ('default_article_type', '""');
INSERT INTO sly_config (id, value) VALUES ('projectname', '"SallyCMS-Projekt"');
INSERT INTO sly_config (id, value) VALUES ('timezone', '"Europe/Berlin"');
INSERT INTO sly_config (id, value) VALUES ('default_locale', '"de_de"');
INSERT INTO sly_config (id, value) VALUES ('addons', '[]');
INSERT;

foreach ($platforms as $name => $platform) {
	$queries = $schema->toSql($platform);
	$queries = array_map('trimSemicolon', $queries);
	$queries = implode(";\n", $queries);

	file_put_contents("$baseDir/install/$name.sql", "$header\n\n$queries;\n\n$footer\n");
}

////////////////////////////////////////////////////////////////////////////////
// some helpers :)

function userCols(Table $table) {
	$table->addColumn('createdate', 'datetime');
	$table->addColumn('updatedate', 'datetime');
	$table->addColumn('createuser', 'string')->setLength(128);
	$table->addColumn('updateuser', 'string')->setLength(128);
}

function createTable(Schema $schema, $name) {
	$table = $schema->createTable($name);

	$table->addOption('engine', 'InnoDB');
	$table->addOption('charset', 'utf8');

	return $table;
}

function trimSemicolon($str) {
	return rtrim($str, ';');
}
