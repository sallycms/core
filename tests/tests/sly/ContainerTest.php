<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_ContainerTest extends PHPUnit_Framework_TestCase {

	public function testBasicSetGet() {
		$container = new sly_Container();
		$container->set('test', 'value');
		$this->assertEquals($container->get('test'), 'value');
		$container['test'] = 'value2';
		$this->assertEquals($container['test'], 'value2');
	}

	public function testHas() {
		$container = new sly_Container();
		$container->set('test', 'value');
		$this->assertTrue(isset($container['test']));
		$this->assertTrue($container->has('test'));
		$this->assertFalse($container->has('not.existing'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMissing() {
		$container = new sly_Container();
		$container->get('test.missing');
	}

	public function testCount() {
		// Basic container has 33 items
		$container = new sly_Container();
		$this->assertEquals($container->count(), 33);
	}

	public function testRemove() {
		$container = new sly_Container();
		$container['test'] = true;
		unset($container['test']);
		$this->assertFalse($container->has('test'));
		unset($container['not.existing']);
		$this->assertFalse($container->has('not.existing'));
	}

}
