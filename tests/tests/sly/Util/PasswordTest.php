<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util_PasswordTest extends PHPUnit_Framework_TestCase {
	public function testHashing() {
		$this->assertNotEquals(sly_Util_Password::hash('a'), sly_Util_Password::hash('a'));
	}

	public function testBcrypt() {
		if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
			$this->markTestSkipped('Blowfish (CRYPT_BLOWFISH) is not available.');
		}

		$this->assertNotEquals(sly_Util_Password::bcrypt('a'), sly_Util_Password::bcrypt('a'));
	}

	public function testPbkdf2() {
		if (!function_exists('hash_algos')) {
			$this->markTestSkipped('Hash extension is not available.');
		}

		$this->assertNotEquals(sly_Util_Password::pbkdf2('a'),          sly_Util_Password::pbkdf2('a'));
		$this->assertNotEquals(sly_Util_Password::pbkdf2('a', 'x'),     sly_Util_Password::pbkdf2('a', 'y'));
		$this->assertNotEquals(sly_Util_Password::pbkdf2('a', 'x', 12), sly_Util_Password::pbkdf2('a', 'x', 13));

		$this->assertEquals(sly_Util_Password::pbkdf2('a', 'dummysalt'),     sly_Util_Password::pbkdf2('a', 'dummysalt'));
		$this->assertEquals(sly_Util_Password::pbkdf2('a', 'dummysalt', 42), sly_Util_Password::pbkdf2('a', 'dummysalt', 42));
	}

	public function testSha1() {
		$this->assertNotEquals(sly_Util_Password::iteratedSha1('a'),          sly_Util_Password::iteratedSha1('a'));
		$this->assertNotEquals(sly_Util_Password::iteratedSha1('a', 'x'),     sly_Util_Password::iteratedSha1('a', 'y'));
		$this->assertNotEquals(sly_Util_Password::iteratedSha1('a', 'x', 12), sly_Util_Password::iteratedSha1('a', 'x', 13));

		$this->assertEquals(sly_Util_Password::iteratedSha1('a', 'dummysalt'),     sly_Util_Password::iteratedSha1('a', 'dummysalt'));
		$this->assertEquals(sly_Util_Password::iteratedSha1('a', 'dummysalt', 42), sly_Util_Password::iteratedSha1('a', 'dummysalt', 42));
	}

	public function testGetRandomData() {
		$rand = sly_Util_Password::getRandomData(12);
		$this->assertEquals(12, strlen($rand));
	}

	/**
	 * @dataProvider verifyProvider
	 */
	public function testVerify($hash, $password, $salt, $expected, $requirementsOK = true) {
		if (!$requirementsOK) {
			$this->markTestSkipped('Cannot verify '.$hash.' because not all required hashing functions are available.');
		}

		$this->assertEquals($expected, sly_Util_Password::verify($password, $hash, $salt));
	}

	public function verifyProvider() {
		$bcrypt = defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH;
		$hmac   = function_exists('hash_algos');

		return array(
			// classical SHA-1 hashes (Sally 0.6-style)
			array('c5e4335577bb89540b15e2f5251e8bc02ced5b32', 'admin', '1302655028', true),
			array('c5e4335577bb89540b15e2f5251e8bc02ced5b32', 'admIn', '1302655028', false), // bad password
			array('c5e433557_bb89540b15e2f5251e8bc02ced5b32', 'admin', '1302655028', false), // bad hash
			array('i\'m not a hash',                          'admin', '1302655028', false), // bad hash
			array('c5e4335577bb89540b15e2f5251e8bc02ced5b32', 'admin', '1302655029', false), // bad salt
			array('d033e22ae348aeb5660fc2140aec35850c4da997', 'admin', '1302655028', false), // only 1 iteration without salt

			// bcrypt
			array('$2a$10$55ca98cff8cb2d53ea435Ox8SZBouvHEfi08ztRxvXFPMN8D6nVmu', 'admin', null, true, $bcrypt),
			array('$2a$10$addcc3f32c34b1df0e167uz/ea2qoJlZCU0G/gfSfVjjSYKGqBQE6', 'admin', null, true, $bcrypt),
			array('$2a$08$606ce888f5d3043aac8c9upcQQFZ4qsFerzvn5U1pKIM/u/idonQS', 'admin', null, true, $bcrypt),  // using a non-default cost factor
			array('$2a$10$addcc3f32c34b1df0e167uz/ea2qoJlZCU0G/gfSfVjjSYKGqBQE6', 'admin', 'not important, will be ignored', true, $bcrypt),
			array('$2a$10$addcc3f32c99b1df0e167uz/ea2qoJlZCU0G/gfSfVjjSYKGqBQE6', 'admin', null, false, $bcrypt), // broken salt
			array('$2a$10$addcc3f32c34b1df0e167uz/ea2qoJlZCU1G/gfSfVjjSYKGqBQE6', 'admin', null, false, $bcrypt), // broken hash
			array('$2a$10$addcc3f32c34b1df0e167uz/ea2qoJlZCU1G/gfSfVjjSYKGqBQE6', 'root',  null, false, $bcrypt), // bad password

			// PBKDF2
			array('$XX$500$760adbcd2106afc07bc7256cf18f0055$11b15eb501afbe25367cbf9a30cbc8baa0ef582ee4197f868e805c206bd70758', 'admin', null, true, $hmac),
			array('$XX$50$1aae471012aefccd5b4ed3a461446fde$03b51b6cf733ad24b346e685a6cee68b61345c1c0ee99eb673ec4d6a90f2850d',  'admin', null, true, $hmac),  // using a non-default iteration count
			array('$XX$500$760adbcd2106afc07bc7256cf18f0055$11b15eb501afbe25367cbf9a30cbc8baa0ef582ee4197f868e805c206bd70758', 'admin', 'not important, will be ignored', true, $hmac),
			array('$XX$500$760adbcd2106h4g5u2zrfd7cf18f0055$11b15eb501afbe25367cbf9a30cbc8baa0ef582ee4197f868e805c206bd70758', 'admin', null, false, $hmac), // broken salt
			array('$XX$500$760adbcd2106afc07bc7256cf18f0055$11b15eb501afbe25367cbf9a3093274z23zrfg76f2197f868e805c206bd70758', 'admin', null, false, $hmac), // broken hash
			array('$XX$500$760adbcd2106afc07bc7256cf18f0055$11b15eb501afbe25367cbf9a30cbc8baa0ef582ee4197f868e805c206bd70758', 'root',  null, false, $hmac), // bad password

			// iterated SHA-1 (Sally 0.7+)
			array('$ZZ$50000$9215359653d94664d748$b33dc9d5e90670b710c110c6780a9ca030b083f0', 'admin', null, true, $bcrypt),
			array('$ZZ$5000$b282ab4d4164659e3da3$80e05db7fc5dc9fb8409c6816a0de7b7fb7c4c9b',  'admin', null, true, $bcrypt), // using a non-default iteration count
			array('$ZZ$50000$9215359653d94664d748$b33dc9d5e90670b710c110c6780a9ca030b083f0', 'admin', 'not important, will be ignored', true, $bcrypt),
			array('$ZZ$50000$9215359111d94664d748$b33dc9d5e90670b710c110c6780a9ca030b083f0', 'admin', null, false, $bcrypt), // broken salt
			array('$ZZ$50000$9215359653d94664d748$b33dc9d5e9045jh34irgzg36780a9ca030b083f0', 'admin', null, false, $bcrypt), // broken hash
			array('$ZZ$50000$9215359653d94664d748$b33dc9d5e90670b710c110c6780a9ca030b083f0', 'root',  null, false, $bcrypt), // bad password
		);
	}

	/**
	 * @expectedException sly_Exception
	 * @dataProvider      brokenHashProvider
	 */
	public function testUnknownHash($hash) {
		sly_Util_Password::verify('dummy', $hash);
	}

	public function brokenHashProvider() {
		return array(
			array('$'),
			array('$$$'),
			array('$BROKEN'),
			array('$42$kgr2ikrwh'),
		);
	}
}
