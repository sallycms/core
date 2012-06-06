<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
	}
}
