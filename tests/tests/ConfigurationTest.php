<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ConfigurationTest extends PHPUnit_Framework_TestCase {

	private function newConfig() {
		$config = new sly_Configuration();
		$config->setFlushOnDestruct(false);
		return $config;
	}

	private function newConfigWithStaticBase() {
		$config = new sly_Configuration();
		$config->setFlushOnDestruct(false);
		$base_array = array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau')
		);
		$config->setStatic('unittest', $base_array);

		return $config;
	}

	public function testAssignScalar() {
		$config = $this->newConfig();

		$config->setStatic('unittest', 'scalar_value');

		$this->assertEquals('scalar_value', $config->get('unittest'), 'setting scalar failed');
	}

	public function testAssignArray() {
		$config = $this->newConfig();

		$config->setStatic('unittest', array('unit' => 'test'));

		$this->assertEquals(array('unit' => 'test'), $config->get('unittest'), 'setting array failed');
	}

	public function testAssingDeep() {
		$config = $this->newConfig();

		$config->setStatic('unittest/deep/thought', 'scalar_value');

		$this->assertEquals(array('deep' => array('thought' => 'scalar_value')), $config->get('unittest'), 'setting scalar in the deep failed');
	}

	public function testOverwriteScalarWithScalar() {
		$config = $this->newConfig();

		$config->setStatic('unittest', 'scalar_value');

		$exception_thrown = false;
		try {
			$config->setStatic('unittest', 'other_scalar');
		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}
		$this->assertTrue($config->get('unittest') == 'other_scalar');
		$this->assertFalse($exception_thrown, 'reasing scalar to scalar failed');
	}

	public function testOverwriteScalarWithArray() {
		$config = $this->newConfig();

		$config->setStatic('unittest', 'scalar_value');
		$exception_thrown = false;
		try {
			$config->setStatic('unittest', array('unit' => 'test'));
		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}
		$this->assertTrue($exception_thrown, 'reasing array to scalar should fail');
	}

	public function testOverwriteStaticWithLocal() {
		$config = $this->newConfig();

		$config->setStatic('unittest', 'scalar_value');

		$exception_thrown = false;
		try {
			$config->setLocal('unittest', 'other_scalar');

		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}

		$this->assertFalse($exception_thrown, 'asing of key existing in other facility failed');
		$this->assertEquals('other_scalar', $config->get('unittest'), 'setting scalar failed');
	}

	public function testOverwriteLocalWithStatic() {
		$config = $this->newConfig();

		$config->setLocal('unittest', 'scalar_value');

		$exception_thrown = false;
		try {
			$config->setStatic('unittest', 'other_scalar');

		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}

		$this->assertFalse($exception_thrown, 'asing of key existing in other facility failed');
		$this->assertEquals('scalar_value', $config->get('unittest'), 'setting scalar failed');
	}


	public function testOverwriteStaticWithProject() {
		$config = $this->newConfig();

		$config->setStatic('unittest', 'scalar_value');

		$exception_thrown = false;
		try {
			$config->set('unittest', 'other_scalar');

		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}

		$this->assertFalse($exception_thrown, 'asing of key existing in other facility failed');
		$this->assertEquals('other_scalar', $config->get('unittest'), 'setting scalar failed');
	}

	public function testOverwriteLocalWithProject() {
		$config = $this->newConfig();

		$config->setLocal('unittest', 'scalar_value');

		$exception_thrown = false;
		try {
			$config->set('unittest', 'other_scalar');

		} catch (sly_Exception $e) {
			$exception_thrown = true;
		}

		$this->assertFalse($exception_thrown, 'asing of key existing in other facility failed');
		$this->assertEquals('other_scalar', $config->get('unittest'), 'setting scalar failed');
	}

	public function testMergeArrayKeys() {
		$config = $this->newConfigWithStaticBase();

		$config->setStatic('unittest/numArray', array('1', '2', '3'));

		$this->assertEquals(array(
			'numArray' => array('1', '2', '3'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau')
		), $config->get('unittest'), 'array merging by key failed');
	}

	public function testMergeArrayValues() {
		$config = $this->newConfigWithStaticBase();

		$config->setStatic('unittest/assocArray', array('yellow' => 'gelb'));

		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau', 'yellow' => 'gelb')
		), $config->get('unittest'), 'array value merging failed');
	}

	public function testMergeScalarToAssoc() {
		$config = $this->newConfigWithStaticBase();

		$config->setStatic('unittest/assocArray', 'gelb');

		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => 'gelb'),
			$config->get('unittest'),
		 'merging scalar to assoc array failed');
	}

	public function testMergeNumArrayToAssoc() {
		$config = $this->newConfigWithStaticBase();

		$config->setStatic('unittest/assocArray', array('gelb'));

		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('gelb')),
			$config->get('unittest'),
		 'merging scalar to assoc array failed');
	}

	public function testOverwriteScalarDeep() {
		$config = $this->newConfigWithStaticBase();

		$config->setStatic('unittest/assocArray/blue', 'heckiheckipatang');

		$this->assertEquals($config->get('unittest'), array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'heckiheckipatang')
		), 'overwriting scalar failed');
	}

	public function testOverwriteScalarDeepLocal() {
		$config = $this->newConfigWithStaticBase();

		$config->setLocal('unittest/assocArray/blue', 'heckiheckipatang');

		$this->assertEquals(array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'heckiheckipatang')
		), $config->get('unittest'), 'overwriting scalar failed');
	}

	public function testOverwriteScalarDeepStatic() {
		$config = $this->newConfig();
		$base_array = array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'blau')
		);
		$config->setLocal('unittest', $base_array);

		$config->setStatic('unittest/assocArray/blue', 'heckiheckipatang');

		$this->assertNotEquals($config->get('unittest') == array(
			'numArray' => array('red', 'green', 'blue'),
			'assocArray' => array('red' => 'rot', 'blue' => 'heckiheckipatang')
		), 'overwriting scalar from local over static should fail');
	}

}