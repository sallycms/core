<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_HTMLTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider isAttributeProvider
	 */
	public function testIsAttribute($value, $expected) {
		$this->assertEquals($expected ? true : false, sly_Util_HTML::isAttribute($value));
	}

	public function isAttributeProvider() {
		return array(
			array(5, 1), array(-5, 1), array('1', 1), array('hello world', 1), array(true, 1),
			array(false, 0), array(null, 0), array('', 0), array(" \t ", 0)
		);
	}

	/**
	 * @dataProvider buildAttributeStringProvider
	 */
	public function testBuildAttributeString($value, $expected) {
		$this->assertEquals($expected, sly_Util_HTML::buildAttributeString($value));
	}

	public function buildAttributeStringProvider() {
		$data = array(
			'foo'   => 'bar',
			'hallo' => '',
			'x'     => true,
			'BAR'   => ' neu ',
			'xy'    => "\t",
			'BLUB ' => 34,
			'html'  => '<hallo>, "welt"',
			'abc'   => null
		);

		$expected = 'foo="bar" x="1" bar="neu" blub="34" html="&lt;hallo&gt;, &quot;welt&quot;"';

		return array(
			array($data, $expected), array(array(), ''),
			array(array('foo' => 'bar'), 'foo="bar"'), array(array('foo' => ''), '')
		);
	}
}
