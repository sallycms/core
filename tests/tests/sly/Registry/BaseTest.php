<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Registry_BaseTest extends sly_BaseTest {
	abstract protected function getRegistry();

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	public function testHas() {
		$reg = $this->getRegistry();

		$reg->set('testkey', 'testval');

		$this->assertTrue($reg->has('testkey'));
	}

	public function testGet() {
		$reg = $this->getRegistry();

		$reg->set('testkey', 'testval');
		$this->assertSame('testval', $reg->get('testkey'));
	}

	public function testRemove() {
		$reg = $this->getRegistry();

		$reg->set('testkey', 'testval');
		$reg->remove('testkey');

		$this->assertFalse($reg->has('testkey'));
	}

	public function testSerialisation() {
		$reg     = $this->getRegistry();
		$testval = array('testval', array('serialisation'));

		$reg->set('testkey', $testval);
		$this->assertSame($testval, $reg->get('testkey'));

		$testval = new sly_Form_ButtonBar();

		$reg->set('testkey2', $testval);
		$this->assertInstanceOf('sly_Form_ButtonBar', $reg->get('testkey2'));
	}

	public function testReturnDefault() {
		$reg = $this->getRegistry();
		$val = $reg->get('notavailable', 'undefined');

		$this->assertEquals('undefined', $val);
	}
}
