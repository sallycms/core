<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_TemplateTest extends PHPUnit_Framework_TestCase {
	private static $filename;
	private static $uniqid;

	public static function setUpBeforeClass() {
		$uniqid   = 'abc'.uniqid();
		$filename = 'template.'.$uniqid.'.php';
		$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * Dieses Template ist ein Beispiel.
 *
 * @sly name    $uniqid
 * @sly title   Mein super tolles Template!!!1elf
 * @sly slots   [links, rechts]
 * @sly modules [gallery, foobar, james]
 * @sly class   [article, meta]
 * @sly custom  42
 */

Hallo REX_ARTICLE[id=1]!

\$x = 4;
print \$x + 5;
TESTFILE;

		$service = sly_Service_Factory::getTemplateService();
		$folder  = $service->getFolder();

		// create test template file

		self::$filename = sly_Util_Directory::join($folder, $filename);
		self::$uniqid   = $uniqid;

		file_put_contents(self::$filename, $testfile);

		try {
			// PHPUnit converts all notices to Exceptions, but in this case we don't really care.
			$service->refresh();
		}
		catch (PHPUnit_Framework_Error $e) {
			// pass...
		}
	}

	public static function tearDownAfterClass() {
		unlink(self::$filename);
	}

	public function testGetParams() {
		$service = sly_Service_Factory::getTemplateService();

		$this->assertEquals(self::$uniqid, $service->get(self::$uniqid, 'name'));
		$this->assertEquals('Mein super tolles Template!!!1elf', $service->getTitle(self::$uniqid));
		$this->assertEquals(42, $service->get(self::$uniqid, 'custom'));
	}
}
