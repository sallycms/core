<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Registry_PersistentTest extends sly_Registry_BaseTest {
	protected function getRegistry() {
		$persistence = sly_Core::getContainer()->getPersistence();

		return new sly_Registry_Persistent($persistence);
	}

	public function testFlush() {
		$reg = $this->getRegistry();

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
