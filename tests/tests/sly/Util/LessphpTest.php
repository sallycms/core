<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
		$here  = dirname(__FILE__);
		$rel   = sly_Util_Directory::getRelative($here);
		$mixin = '.clear() { overflow: auto; }';
		file_put_contents($here.DIRECTORY_SEPARATOR.'mixin.less', $mixin);
		sly_Core::config()->setStatic('less_import_dirs', array($rel));

		$input  = "@import 'mixin.less'; a { .clear; }";
		$output = sly_Util_Lessphp::processString($input);
		unlink($here.DIRECTORY_SEPARATOR.'mixin.less');

		$this->assertEquals('a{overflow:auto;}', $output);
	}
}
