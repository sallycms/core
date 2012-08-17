<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_DispatcherTest extends PHPUnit_Framework_TestCase {
	const EVENT = 'SLY_TESTING_DUMMY_EVENT';

	private static $d     = null;
	private        $state = array();

	public function listenerA($params) {
		$this->state[] = 'a';
		return null;
	}

	public function listenerB($params) {
		$this->state[] = 'b';
		return false;
	}

	public function listenerC($params) {
		$this->state[] = 'c';
		return true;
	}

	public function listenerD($params) {
		$this->state[] = 'd';
		return 'hello world';
	}

	public function listenerE($params) {
		$this->state[] = 'e';

		$this->assertArrayHasKey('subject', $params);
		$this->assertArrayHasKey('event', $params);

		return $params['subject'];
	}

	public function listenerF($params) {
		$this->state[] = 'f';
		return strtoupper($params['subject']);
	}

	public function listenerG($params) {
		$this->state[] = 'g';
		return $params['subject'].'s';
	}

	public static function setUpBeforeClass() {
		self::$d = new sly_Event_Dispatcher();
	}

	public function setUp() {
		self::$d->clear(self::EVENT);
	}

	public function testNoListeners() {
		$this->assertFalse(self::$d->hasListeners(self::EVENT));
		$this->assertEquals(array(), self::$d->getListeners(self::EVENT));
	}

	public function testAddListeners() {
		self::$d->register(self::EVENT, array($this, 'listenerA'));
		self::$d->register(self::EVENT, array($this, 'listenerB'));

		$this->assertEquals(2, count(self::$d->getListeners(self::EVENT)));

		self::$d->register(self::EVENT, array($this, 'listenerB'));
		self::$d->register(self::EVENT, array($this, 'listenerA'));

		$this->assertEquals(4, count(self::$d->getListeners(self::EVENT)));
		$this->assertContains(self::EVENT, self::$d->getEvents());
	}

	/**
	 * @dataProvider dispatcherProvider
	 */
	public function testDispatcher($listeners, $method, $subject, $expectedResult = null, $event = null, $expectedListeners = null) {
		// register listeners
		foreach (array_filter(explode(',', $listeners)) as $listener) {
			self::$d->register(self::EVENT, array($this, 'listener'.strtoupper($listener)));
		}

		// reset state & execute desired method
		$this->state = array();
		$result = self::$d->$method($event === null ? self::EVENT : $event, $subject);

		// compare result, if needed
		if ($expectedResult !== null) {
			$this->assertEquals($expectedResult, $result);
		}

		// compare state
		$this->assertEquals($expectedListeners === null ? $listeners : $expectedListeners, implode(',', $this->state));
	}

	public function dispatcherProvider() {
		return array(
			array('',        'notify',      'foo',  0, self::EVENT.'_'),
			array('a,b,a,d', 'notify',      'foo',  4),
			array('a,b',     'notifyUntil', 'foo',  false),
			array('a,c,b',   'notifyUntil', 'foo',  true, null, 'a,c'),
			array('f,g',     'filter',      'test', 'TESTs'),
			array('f,g,f',   'filter',      'test', 'TESTS')
		);
	}
}
