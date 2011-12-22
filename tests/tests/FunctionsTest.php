<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_FunctionsTest extends PHPUnit_Framework_TestCase {
	public function testSlyMakeArray() {
		$this->assertEquals(sly_makeArray(null),         array());
		$this->assertEquals(sly_makeArray(1),            array(1));
		$this->assertEquals(sly_makeArray(true),         array(true));
		$this->assertEquals(sly_makeArray(false),        array(false));
		$this->assertEquals(sly_makeArray(array()),      array());
		$this->assertEquals(sly_makeArray(array(1,2,3)), array(1,2,3));
	}

	public function testRexParamString() {
		$this->assertEquals('&foo=bar', sly_Util_HTTP::queryString(array('foo' => 'bar'), '&'));
		$this->assertEquals('&foo=%C3%9F%24', sly_Util_HTTP::queryString(array('foo' => 'ß$'), '&'));
		$this->assertEquals('foo=bar', 'foo=bar');
	}
}
