<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class RegistryTest extends sly_BaseTest {

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	protected function _testHas(sly_Registry_Registry $reg) {
		$reg->set('testkey', 'testval');
		$this->assertTrue($reg->has('testkey'));
	}

	protected function _testGet(sly_Registry_Registry $reg) {
		$reg->set('testkey', 'testval');
		$val = $reg->get('testkey');
		$this->assertEquals($val, 'testval');
	}

	protected function _testRemove(sly_Registry_Registry $reg) {
		$reg->set('testkey', 'testval');
		$reg->remove('testkey');
		$this->assertFalse($reg->has('testkey'));
	}

	protected function _testSerialisation(sly_Registry_Registry $reg) {
		$testval = array('testval',array('serialisation'));
		$reg->set('testkey', $testval);
		$val = $reg->get('testkey');
		$this->assertEquals($testval, $val);

		$testval2 = new sly_Form_ButtonBar();
		$reg->set('testkey2', $testval2);
		$val = $reg->get('testkey2');
		$this->assertInstanceOf('sly_Form_ButtonBar', $val);
	}

	protected function _testReturnDefault(sly_Registry_Registry $reg) {
		$val = $reg->get('notavailable', 'undefined');
		$this->assertEquals('undefined', $val);
	}

	public function testTempHas() {
		$this->_testHas(sly_Registry_Temp::getInstance());
	}

	public function testTempGet() {
		$this->_testGet(sly_Registry_Temp::getInstance());
	}

	public function testTempRemove() {
		$this->_testRemove(sly_Registry_Temp::getInstance());
	}

	public function testTempReturnDefault() {
		$this->_testReturnDefault(sly_Registry_Temp::getInstance());
	}

	public function testPersistentHas() {
		$this->_testHas(sly_Registry_Persistent::getInstance());
	}

	public function testPersistentGet() {
		$this->_testGet(sly_Registry_Persistent::getInstance());
	}

	public function testPersistentRemove() {
		$this->_testRemove(sly_Registry_Persistent::getInstance());
	}

	public function testPersistentSerialisation() {
		$this->_testSerialisation(sly_Registry_Persistent::getInstance());
	}

	public function testPersistentReturnDefault() {
		$this->_testReturnDefault(sly_Registry_Persistent::getInstance());
	}

	public function testPersistentFlush() {
		$reg = sly_Registry_Persistent::getInstance();
		$reg->set('t1', 'test');
		$reg->set('t2', 'value');
		$reg->set('t3', 'nr3');
		$reg->set('x1', 'muh');
		$reg->flush('t*');
		$this->assertFalse($reg->has('t1'));
		$this->assertFalse($reg->has('t2'));
		$this->assertFalse($reg->has('t3'));
		$this->assertTrue($reg->has('x1'));
	}

}
