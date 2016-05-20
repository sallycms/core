<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ConfigurationTest extends PHPUnit_Framework_TestCase {
	private $config;
	private $test_array;

	public function setUp() {
		$this->config = new sly_Configuration();
		$this->test_array = array(
			'numArray'   => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau')
		);
	}

	private function setBaseArray($static = false) {
		if ($static) {
			$this->config->setStatic('unittest', $this->test_array);
		}
		else {
			$this->config->set('unittest', $this->test_array);
		}
	}

	public function testAssignScalar() {
		$this->config->setStatic('unittest', 'scalar_value');
		$this->assertTrue($this->config->has('unittest'), 'setting scalar failed');
		$this->assertSame('scalar_value', $this->config->get('unittest'), 'setting scalar failed');
	}

	public function testAssignArray() {
		$this->config->setStatic('unittest', array('unit' => 'test'));
		$this->assertSame(array('unit' => 'test'), $this->config->get('unittest'), 'setting array failed');
	}

	public function testAssingDeep() {
		$this->config->setStatic('unittest/deep/thought', 'scalar_value');
		$this->assertSame(array('deep' => array('thought' => 'scalar_value')), $this->config->get('unittest'), 'setting scalar in the deep failed');
	}

	public function testOverwriteScalarWithScalar() {
		$this->config->setStatic('unittest', 'scalar_value');
		$this->config->setStatic('unittest', 'other_scalar');

		$this->assertSame('other_scalar', $this->config->get('unittest'));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testOverwriteScalarWithArray() {
		$this->config->setStatic('unittest', 'scalar_value');
		$this->config->setStatic('unittest', array('unit' => 'test'));
	}

	public function testOverwriteStaticWithProject() {
		$this->config->setStatic('unittest', 'scalar_value');
		$this->config->set('unittest', 'other_scalar');

		$this->assertSame('other_scalar', $this->config->get('unittest'), 'setting scalar failed');
	}

	public function testMergeArrayKeys() {
		$this->setBaseArray(true);
		$this->config->setStatic('unittest/numArray', array('1', '2', '3'));

		$this->assertSame(array(
			'numArray'   => array('1', '2', '3'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau')
		), $this->config->get('unittest'), 'array merging by key failed');
	}

	public function testMergeArrayValues() {
		$this->setBaseArray(true);
		$this->config->setStatic('unittest/assocArray', array('yellow' => 'gelb'));

		$this->assertEquals(array(
			'numArray'   => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau', 'yellow' => 'gelb')
		), $this->config->get('unittest'), 'array value merging failed');
	}

	public function testMergeScalarToAssoc() {
		$this->setBaseArray(true);
		$this->config->setStatic('unittest/assocArray', 'gelb');
		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => 'gelb'),
			$this->config->get('unittest'),
		 'merging scalar to assoc array failed');
	}

	public function testMergeNumArrayToAssoc() {
		$this->setBaseArray(true);
		$this->config->setStatic('unittest/assocArray', array('gelb'));
		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('gelb')),
			$this->config->get('unittest'),
		 'merging scalar to assoc array failed');
	}

	public function testOverwriteScalarDeep() {
		$this->setBaseArray(true);
		$this->config->set('unittest/assocArray/blue', 'heckiheckipatang');
		$this->assertEquals($this->config->get('unittest'), array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'heckiheckipatang')
		), 'overwriting scalar failed');
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testOverwriteScalarDeepStatic() {
		$this->setBaseArray();
		$this->config->setStatic('unittest/assocArray/blue', 'heckiheckipatang');
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testInvalidKey() {
		$this->config->setStatic('', 'heckiheckipatang');
	}
}
