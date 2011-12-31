<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_MimeTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider getTypeProvider
	 */
	public function testGetType($filename, $expected) {
		$this->assertEquals($expected, sly_Util_Mime::getType(SLY_BASE.'/'.$filename));
	}

	public function getTypeProvider() {
		return array(
			array('sally/backend/assets/js/jquery.min.js', 'application/javascript'),
			array('sally/backend/assets/css/sally.css', 'text/css'),
			array('sally/backend/assets/body.png', 'image/png')
		);
	}
}
