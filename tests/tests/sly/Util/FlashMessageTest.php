<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_FlashMessageTest extends PHPUnit_Framework_TestCase {
	public function testSimpleStuff() {
		$msg = new sly_Util_FlashMessage('phpunit');
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_INFO));
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_WARNING));

		$msg->addInfo('Info 1');
		$msg->addInfo('Info 2');
		$this->assertEquals(array('Info 1', 'Info 2'), $msg->getMessages(sly_Util_FlashMessage::TYPE_INFO));
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_WARNING));

		$msg->addWarning('Warning 1');
		$this->assertEquals(array('Info 1', 'Info 2'), $msg->getMessages(sly_Util_FlashMessage::TYPE_INFO));
		$this->assertEquals(array('Warning 1'), $msg->getMessages(sly_Util_FlashMessage::TYPE_WARNING));

		$msg->clear();
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_INFO));
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_WARNING));

		$msg->addInfo('Info 1');
		$msg->addWarning('Warning 1');
		$msg->clear(sly_Util_FlashMessage::TYPE_INFO);
		$this->assertEquals(array(), $msg->getMessages(sly_Util_FlashMessage::TYPE_INFO));
		$this->assertEquals(array('Warning 1'), $msg->getMessages(sly_Util_FlashMessage::TYPE_WARNING));
	}

	/**
	 * @depends testSimpleStuff
	 */
	public function testAppending() {
		$msg  = new sly_Util_FlashMessage('phpunit');
		$info = sly_Util_FlashMessage::TYPE_INFO;

		$msg->addInfo('Info 1');
		$msg->addInfo('Info 2');
		$msg->appendInfo('Zweites Element');
		$this->assertEquals(array('Info 1', array('Info 2', 'Zweites Element')), $msg->getMessages($info));

		$msg->appendInfo('Drittes Element');
		$this->assertEquals(array('Info 1', array('Info 2', 'Zweites Element', 'Drittes Element')), $msg->getMessages($info));

		$msg->addInfo('Info 3');
		$msg->appendInfo('Foo');
		$this->assertEquals(array('Info 1', array('Info 2', 'Zweites Element', 'Drittes Element'), array('Info 3', 'Foo')), $msg->getMessages($info));

		$msg->clear();
		$msg->appendInfo('Test');
		$this->assertEquals(array('Test'), $msg->getMessages($info));
	}

	/**
	 * @depends testAppending
	 */
	public function testPrepending() {
		$msg  = new sly_Util_FlashMessage('phpunit');
		$info = sly_Util_FlashMessage::TYPE_INFO;

		$msg->addInfo('Info 1');
		$msg->addInfo('Info 2');
		$msg->prependInfo('Zweites Element', false);
		$this->assertEquals(array(array('Zweites Element', 'Info 1'), 'Info 2'), $msg->getMessages($info));

		$msg->prependInfo('Drittes Element', false);
		$this->assertEquals(array(array('Drittes Element', 'Zweites Element', 'Info 1'), 'Info 2'), $msg->getMessages($info));

		$msg->prependInfo('Foo', true);
		$this->assertEquals(array('Foo', array('Drittes Element', 'Zweites Element', 'Info 1'), 'Info 2'), $msg->getMessages($info));
	}
}
