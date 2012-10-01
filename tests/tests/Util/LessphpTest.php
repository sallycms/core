<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_LessphpTest extends PHPUnit_Framework_TestCase {
	public function testCompiling() {
		$input  = 'a { color: red; &.foo { color: blue; } }';
		$output = sly_Util_Lessphp::processString($input);

		$this->assertEquals('a{color:red;}a.foo{color:blue;}', $output);
	}

	public function testCompilingFile() {
		$input = 'a { color: red; &.foo { color: blue; } }';
		file_put_contents('test.css', $input);

		$output = sly_Util_Lessphp::process('test.css');
		unlink('test.css');

		$this->assertEquals('a{color:red;}a.foo{color:blue;}', $output);
	}

	public function testImports() {
		$input  = "@import 'layout.less'; a { .clear; }";
		$output = sly_Util_Lessphp::processString($input);

		$this->assertEquals('a{overflow:auto;}', $output);
	}
}
