<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_PasswordTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider hashingProvider
	 */
	public function testHashing($a, array $aSalts, $b, array $bSalts) {
		$this->assertNotEquals(sly_Util_Password::hash($a, $aSalts), sly_Util_Password::hash($b, $bSalts));
	}

	public function hashingProvider() {
		return array(
			array('test', array(), 'test2', array()),
			array('test', array(), 'test', array('a')),
			array('test', array('a'), 'test', array('b')),
			array('test', array('a', 'b'), 'test', array('b', 'a')),
			array('test', array('0'), 'test', array(''))
		);
	}

	public function testLength() {
		$this->assertEquals(40, strlen(sly_Util_Password::hash('test')));
	}

	/**
	 * @dataProvider emptyProvider
	 */
	public function testEmpty($val) {
		$this->assertEquals(sly_Util_Password::hash('test', array($val)), sly_Util_Password::hash('test'));
	}

	public function emptyProvider() {
		return array(array(''), array(false), array(null));
	}
}
