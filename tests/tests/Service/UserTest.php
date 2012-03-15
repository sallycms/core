<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_UserTest extends sly_BaseTest {
	protected function getDataSetName() {
		return 'pristine-sally';
	}

	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getUserService();
		return $service;
	}

	public function testCreate() {
		$service = $this->getService();
		$login   = 'a'.uniqid();
		$user    = $service->create(array(
			'login'       => $login,
			'name'        => 'Tom Tester',
			'description' => 'Tom only exists during unit tests.',
			'status'      => true,
			'createuser'  => 'phpunit',
			'updateuser'  => 'phpunit',
			'psw'         => 'mumblefoo'
		));

		$this->assertInstanceOf('sly_Model_User', $user);
		$this->assertEquals($login, $user->getLogin());
		$this->assertEquals('Tom Tester', $user->getName());
		$this->assertEquals('Tom only exists during unit tests.', $user->getDescription());
		$this->assertEquals(true, $user->getStatus());
		$this->assertNotEquals('mumblefoo', $user->getPassword());
		$this->assertFalse($user->isAdmin());

		$this->assertCount(2, $service->find());
		$this->assertInstanceOf('sly_Model_User', $service->findById($user->getId()));
		$this->assertInstanceOf('sly_Model_User', $service->findByLogin($login));
	}

	/**
	 * @depends testCreate
	 */
	public function testAdd() {
		$service = $this->getService();
		$login   = 'a'.uniqid();
		$user    = $service->add($login, 'mumblefoo', true, '');

		$this->assertInstanceOf('sly_Model_User', $user);
		$this->assertEquals($login, $user->getLogin());
		$this->assertEquals(true, $user->getStatus());
		$this->assertNotEquals('mumblefoo', $user->getPassword());
		$this->assertFalse($user->isAdmin());
	}

	/**
	 * @depends testAdd
	 */
	public function testEdit() {
		$service = $this->getService();
		$user    = $service->add('a'.uniqid(), 'mumblefoo', true, '');
		$hash    = $user->getPassword();

		$user->setName('F. Oooh');
		$user->setStatus(false);
		$user->setPassword('test');
		$service->save($user);

		$user = $service->findById($user->getId());
		$this->assertEquals('F. Oooh', $user->getName());
		$this->assertEquals(0, $user->getStatus());
		$this->assertNotEquals($hash, $user->getPassword());
	}

	/**
	 * @depends testAdd
	 */
	public function testKeepsPassword() {
		$service = $this->getService();
		$user    = $service->add('a'.uniqid(), 'mumblefoo', true, '');
		$hash    = $user->getPassword();

		$user->setName('F. Oooh');
		$service->save($user);

		$user = $service->findById($user->getId());
		$this->assertEquals($hash, $user->getPassword());
	}

	/**
	 * @depends testCreate
	 */
	public function testSalting() {
		$service = $this->getService();
		$userA   = $service->create(array('login' => 'a'.uniqid(), 'psw' => 'mumblefoo', 'createdate' => time()));
		$userB   = $service->create(array('login' => 'b'.uniqid(), 'psw' => 'mumblefoo', 'createdate' => time()-10));

		$this->assertNotEquals(sha1('mumblefoo'), $userA->getPassword());      // any salting at all?
		$this->assertNotEquals($userA->getPassword(), $userB->getPassword());  // user-specific salts?
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testMissingLogin() {
		$this->getService()->create(array('psw' => 'mumblefoo'));
	}

	/**
	 * @expectedException sly_Exception
	 */
	public function testMissingPassword() {
		$this->getService()->create(array('login' => 'a'.uniqid()));
	}

	/**
	 * @depends testCreate
	 */
	public function testZeroData() {
		$user = $this->getService()->create(array('login' => '0', 'psw' => '0'));
		$this->assertEquals('0', $user->getLogin());
	}

	/**
	 * @depends testCreate
	 */
	public function testDelete() {
		$service = $this->getService();
		$user    = $service->create(array('login' => '0', 'psw' => '0'));
		$id      = $user->getId();

		$service->delete(array('id' => $id));

		$this->assertNull($service->findById($id));
		$this->assertCount(1, $service->find());
	}
}
