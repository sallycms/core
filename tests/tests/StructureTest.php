<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_StructureTest extends sly_DatabaseTest {
	private static $origStart;
	private static $origNotFound;

	public static function setUpBeforeClass() {
		$conf = sly_Core::config();

		self::$origStart    = $conf->get('START_ARTICLE_ID');
		self::$origNotFound = $conf->get('NOTFOUND_ARTICLE_ID');

		$conf->set('START_ARTICLE_ID', 0);
		$conf->set('NOTFOUND_ARTICLE_ID', 0);
	}

	public static function tearDownAfterClass() {
		$conf = sly_Core::config();

		$conf->set('START_ARTICLE_ID', self::$origStart);
		$conf->set('NOTFOUND_ARTICLE_ID', self::$origNotFound);
	}
}
