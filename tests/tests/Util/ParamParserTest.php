<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_ParamParserTest extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$filename = dirname(__FILE__).'/template1.php';
		$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * Dieses Template ist ein Beispiel. Bla bla bla.
 *
 * @sly
 * @sly test_boolean true
 * @sly test_int     45
 * @sly test_double  194.234
 * @sly test_array   [foo, bar, 34, true]
 * @sly test_hash    {name: blub, second: 34}
 * @sly test_string  Dies ist nur ein kleiner String. Nichts besonderes.
 * Hier
 *   folgt
        weiterer
 *  Text.
 *
 * @sly second_group reached!
 *
 * Dieser Text sollte nicht mehr Teil von second_group sein.
 *
 * Der hier auch nicht mehr.
 *
 */

\$x = 4;
print \$x + 5;
TESTFILE;

		file_put_contents($filename, $testfile);

		$filename = dirname(__FILE__).'/template2.php';
		$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * @sly test_invalid {foo:}
 */

\$x = 4;
print \$x + 5;
TESTFILE;

		file_put_contents($filename, $testfile);

		$filename = dirname(__FILE__).'/template3.php';
		$testfile = "<?php\nprint 'Hallo Welt!';";

		file_put_contents($filename, $testfile);

		$filename = dirname(__FILE__).'/template4.php';
		$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * @sly test foo
 * @sly
 * @sly foo test
 */

\$x = 4;
print \$x + 5;
TESTFILE;

		file_put_contents($filename, $testfile);
	}

	public static function tearDownAfterClass() {
		unlink(dirname(__FILE__).'/template1.php');
		unlink(dirname(__FILE__).'/template2.php');
		unlink(dirname(__FILE__).'/template3.php');
		unlink(dirname(__FILE__).'/template4.php');
	}

	public function testParsing() {
		$filename = dirname(__FILE__).'/template1.php';
		$parser   = new sly_Util_ParamParser($filename);
		$params   = array(
			'test_boolean' => true,
			'test_int'     => 45,
			'test_double'  => 194.234,
			'test_array'   => array('foo', 'bar', 34, true),
			'test_hash'    => array('name' => 'blub', 'second' => 34),
			'test_string'  => 'Dies ist nur ein kleiner String. Nichts besonderes. Hier folgt weiterer Text.',
			'second_group' => 'reached!'
		);

		$this->assertSame($params, $parser->get());
		$this->assertSame(194.234, $parser->get('test_double'));
		$this->assertSame('mydefault', $parser->get('missing', 'mydefault'));

		return $parser;
	}

	public function testEmpty() {
		$filename = dirname(__FILE__).'/template3.php';
		$parser   = new sly_Util_ParamParser($filename);

		$this->assertEquals(array(), $parser->get());
	}

	/**
	 * @depends testParsing
	 */
	public function testCachedResults(sly_Util_ParamParser $parser) {
		$filename = dirname(__FILE__).'/template1.php';
		$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * @sly test_boolean false
 */

\$x = 5;
TESTFILE;

		file_put_contents($filename, $testfile);
		$this->assertTrue($parser->get('test_boolean')); // <- cached value

		// Do new instances correctly reload the file?

		$parser = new sly_Util_ParamParser($filename);
		$params = array('test_boolean' => false);

		$this->assertEquals($params, $parser->get());
		$this->assertFalse($parser->get('test_boolean'));
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInvalidValue() {
		$parser = new sly_Util_ParamParser(dirname(__FILE__).'/template2.php');
		$params = array('test_invalid' => '{foo:}');

		$this->assertEquals($params, $parser->get());
	}

	public function testInvalidTag() {
		$parser = new sly_Util_ParamParser(dirname(__FILE__).'/template4.php');
		$params = array('test' => 'foo', 'foo' => 'test');

		$this->assertEquals($params, $parser->get());
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testMissingFile() {
		new sly_Util_ParamParser('nonexisting.php');
	}
}
