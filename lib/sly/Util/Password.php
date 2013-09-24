<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Password hashing and verification helper
 *
 * @ingroup util
 */
class sly_Util_Password {
	const MAX_PASSWORD_LENGTH = 4096;  ///< int     max allowed length of a password
	const BCRYPT_COST_FACTOR  = 10;    ///< int     remember, this is used exponentially
	const ITERATIONS_PBKDF2   = 500;   ///< int     iteration count for PBKDF2
	const ITERATIONS_SHA1     = 50000; ///< int     iteration count for fast hashing algorithms like SHA-*
	const ALGORITHM_BLOWFISH  = '2a';  ///< string  aka bcrypt
	const ALGORITHM_PBKDF2    = 'XX';  ///< string  pseudo-identifier
	const ALGORITHM_SHA1      = 'ZZ';  ///< string  pseudo-identifier to let us know we had to fall back to SHA-1

	/**
	 * Hash a password
	 *
	 * This method will use the best available hashing algorithm to create a
	 * strong hash. Be aware that this method is *not* stable anymore since
	 * Sally 0.7, so calling it multiple times will result in different hashes
	 * (because the hashes will contain random salts).
	 *
	 * @see    http://php.net/manual/en/function.crypt.php
	 * @param  string $password
	 * @return string
	 */
	public static function hash($password) {
		self::checkPasswordLength($password);

		// total #win: use bcrypt
		if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH) {
			return self::bcrypt($password);
		}

		// not so #win, but okay: hash extension
		if (function_exists('hash_algos')) {
			return self::pbkdf2($password);
		}

		// #fail: can only use SHA-1
		return self::iteratedSha1($password);
	}

	public static function verify($password, $hash, $saltForOldschoolHash = null) {
		self::checkPasswordLength($password);

		if (strlen($hash) === 0) return false;

		// assume old-school hashing (Sally < 0.7)
		if ($hash[0] !== '$') {
			if ($saltForOldschoolHash === null) {
				throw new sly_Exception('For hashes generated before Sally 0.7, you have to provide the originally used salt manually.');
			}

			$it   = 100;
			$salt = str_repeat((string) $saltForOldschoolHash, 15);

			for ($i = 0; $i < $it; ++$i) $password = sha1($password);
			$password = $password.$salt;
			for ($i = 0; $i < $it; ++$i) $password = sha1($password);

			$computed = $password;
		}
		else {
			$parts = explode('$', $hash, 3);

			if (count($parts) !== 3) {
				throw new sly_Exception('Unknown hash format given.');
			}

			list ($unused, $id, $tail) = $parts;

			switch ($id) {
				case self::ALGORITHM_BLOWFISH:
					if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
						throw new sly_Exception('bcrypt is not supported on this machine.');
					}

					// crypt() takes care of splitting the hash into salt and whatnot
					$computed = crypt($password, $hash);
					break;

				case self::ALGORITHM_PBKDF2:
					if (!function_exists('hash_algos') || !in_array('sha256', hash_algos())) {
						throw new sly_Exception('SHA-256 (hash extension) is not supported on this machine.');
					}

					$parts    = explode('$', $tail, 3);
					$computed = self::pbkdf2($password, $parts[1], (int) $parts[0]);
					break;

				case self::ALGORITHM_SHA1:
					$parts    = explode('$', $tail, 3);
					$computed = self::iteratedSha1($password, $parts[1], (int) $parts[0]);
					break;

				default:
					throw new sly_Exception('Unknown hash identifier "'.$id.'" found.');
			}
		}

		return self::equals($computed, $hash);
	}

	/**
	 * Compare two strings length-indenpendently
	 *
	 * This method implements a constant-time algorithm to compare passwords to
	 * avoid (remote) timing attacks.
	 *
	 * @param  string $strA  string A
	 * @param  string $strB  string B
	 * @return boolean       true if match, else false
	 */
	public static function equals($strA, $strB) {
		if (strlen($strA) !== strlen($strB)) {
			return false;
		}

		$result = 0;

		for ($i = 0; $i < strlen($strA); ++$i) {
			$result |= ord($strA[$i]) ^ ord($strB[$i]);
		}

		return 0 === $result;
	}

	public static function upgrade($password, $oldHash) {
		// old-school password hashes should always be upgraded
		if ($oldHash[0] !== '$') {
			return self::hash($password);
		}

		list ($unused, $id, $tail) = explode('$', $oldHash, 3);

		switch ($id) {
			case self::ALGORITHM_BLOWFISH:
				list ($costs, $rest) = explode('$', $tail, 2);

				$upgrade = $costs < self::BCRYPT_COST_FACTOR;

				break;

			case self::ALGORITHM_PBKDF2:
				list ($iterations, $rest) = explode('$', $tail, 2);

				$tooFewIterations = $iterations < self::ITERATIONS_PBKDF2;
				$hasBcrypt        = defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH;
				$upgrade          = $tooFewIterations || $hasBcrypt;

				break;

			case self::ALGORITHM_SHA1:
				list ($iterations, $rest) = explode('$', $tail, 2);

				$tooFewIterations = $iterations < self::ITERATIONS_SHA1;
				$hasBcrypt        = defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH;
				$hasHashExt       = function_exists('hash_hmac');
				$upgrade          = $tooFewIterations || $hasBcrypt || $hasHashExt;

				break;

			default:
				throw new sly_Exception('Unknown hash identifier "'.$id.'" found.');
		}

		return $upgrade ? self::hash($password) : null;
	}

	/**
	 * Blowfish/bcrypt based hashing
	 *
	 * The resulting string contains the algorithm identifier, salt, hash and
	 * could look like this:
	 *
	 * $2a$11$55d8b827ece4799484d67u6l5O8XSzrXcw4zDWKuUcfDxqai7BZJm
	 *
	 * @param  string $password
	 * @return string
	 */
	public static function bcrypt($password) {
		// salt may be at most 22 characters long and must be [a-zA-Z0-9./]
		$salt = bin2hex(self::getRandomData(11));
		$salt = sprintf('$%s$%02d$%s', self::ALGORITHM_BLOWFISH, self::BCRYPT_COST_FACTOR, $salt);

		return crypt($password, $salt);
	}

	/**
	 * PBKDF2 implementation
	 *
	 * The resulting string contains the algorithm identifier, salt, hash and
	 * could look like this:
	 *
	 * $5$150$b5d44030e945eb3b41b936a75c43380d$ea2253bf2c4854ec07b643bd6b80afc8d6b680c77600af7fcc50cb5b1190dd1d
	 *
	 * @see http://php.net/manual/en/function.hash-hmac.php#108966
	 * @see https://github.com/rwky/rwky-php-pbkdf2
	 *
	 * @param  string $password    the password to hash
	 * @param  string $salt        salt
	 * @param  int    $iterations  iteration count
	 * @return string
	 */
	public static function pbkdf2($password, $salt = null, $iterations = null) {
		$algo = 'sha256';
		$hash = '';
		$salt = $salt === null ? bin2hex(self::getRandomData(16)) : $salt;
		$it   = $iterations === null ? self::ITERATIONS_PBKDF2 : (int) $iterations;
		$len  = strlen(hash($algo, '', true));

		// create key
		for ($block = 1; $block <= $len; ++$block) {
			// initial hash for this block
			$ib = $h = hash_hmac($algo, $salt.pack('N', $block), $password, true);

			// perform block iterations
			for ($i = 1; $i < $it; ++$i) {
				$ib ^= ($h = hash_hmac($algo, $h, $password, true));
			}

			// append iterated block
			$hash .= $ib;
		}

		$hash = substr($hash, 0, $len);
		$hash = bin2hex($hash);

		return sprintf('$%s$%d$%s$%s', self::ALGORITHM_PBKDF2, $it, $salt, $hash);
	}

	/**
	 * Primitive SHA-1 iteration with salt
	 *
	 * The resulting string contains the algorithm identifier, salt, hash and
	 * could look like this:
	 *
	 * $XX$100000$72c62fc552c5ca1b2eabc6d6562a2d99$699b6bb5cf7c92682b047a14b66ac8ce10a05a2b
	 *
	 * @param  string $password
	 * @param  string $salt
	 * @param  int    $iterations
	 * @return string
	 */
	public static function iteratedSha1($password, $salt = null, $iterations = null) {
		$salt = $salt === null ? bin2hex(self::getRandomData(10)) : $salt;
		$it   = $iterations === null ? self::ITERATIONS_SHA1 : (int) $iterations;
		$hash = sha1($salt.$password.$salt);

		for ($i = 0; $i < $it; ++$i) {
			$hash = sha1($salt.$password.$salt.$hash);
		}

		return sprintf('$%s$%d$%s$%s', self::ALGORITHM_SHA1, $it, $salt, $hash);
	}

	/**
	 * Collect pseudo random bytes
	 *
	 * @param  int     $bytes
	 * @param  boolean $base64Encode  set to true to encode the random data automatically
	 * @return string
	 */
	public static function getRandomData($bytes, $base64Encode = false) {
		// PHP 5.3 + openssl FTW
		if (function_exists('openssl_random_pseudo_bytes')) {
			$data = openssl_random_pseudo_bytes($bytes);
		}
		else {
			$data = '';

			// *nix FTW
			if (@is_readable('/dev/urandom') && ($fh = @fopen('/dev/urandom', 'rb'))) {
				$data = fread($fh, $bytes);
				fclose($fh);
			}

			// append kind of random data to the already available data
			// (not cryptographically strong, but we're out of options here)
			for ($i = strlen($data); $i < $bytes; ++$i) {
				$data .= chr(mt_rand(0, 255));
			}
		}

		return $base64Encode ? base64_encode($data) : $data;
	}

	public static function checkPasswordLength($password) {
		if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
			throw new sly_Exception('Invalid password.');
		}
	}
}
