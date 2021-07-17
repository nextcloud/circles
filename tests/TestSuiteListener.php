<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Tests;

use OCA\Circles\Model\DeprecatedCircle;

class Env implements \PHPUnit_Framework_TestListener {
	public const ENV_TEST_OWNER1 = '_test_circles_owner1';
	public const ENV_TEST_OWNER2 = '_test_circles_owner2';
	public const ENV_TEST_OWNER3 = '_test_circles_owner3';

	public const ENV_TEST_ADMIN1 = '_test_circles_admin1';
	public const ENV_TEST_ADMIN2 = '_test_circles_admin2';
	public const ENV_TEST_ADMIN3 = '_test_circles_admin3';

	public const ENV_TEST_MODERATOR1 = '_test_circles_mod1';
	public const ENV_TEST_MODERATOR2 = '_test_circles_mod2';
	public const ENV_TEST_MODERATOR3 = '_test_circles_mod3';

	public const ENV_TEST_MEMBER1 = '_test_circles_member1';
	public const ENV_TEST_MEMBER2 = '_test_circles_member2';
	public const ENV_TEST_MEMBER3 = '_test_circles_member3';

	public const ENV_TEST_USER1 = '_test_circles_user1';
	public const ENV_TEST_USER2 = '_test_circles_user2';
	public const ENV_TEST_USER3 = '_test_circles_user3';


	/** @var array<string> */
	private $users;

	public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time) {
	}

	public function addFailure(
		\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time
	) {
	}

	public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {
	}

	public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {
	}

	public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {
	}

	public function startTest(\PHPUnit_Framework_Test $test) {
	}

	public function endTest(\PHPUnit_Framework_Test $test, $time) {
	}

	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		if ($suite->getName() !== '.') {
			return;
		}

		$userManager = \OC::$server->getUserManager();
		$this->users = self::listUsers();

		foreach ($this->users as $UID) {
			if ($userManager->userExists($UID) === false) {
				$userManager->createUser($UID, $UID);
			}
		}
	}

	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		if ($suite->getName() !== '.') {
			return;
		}

		foreach ($this->users as $UID) {
			$user = \OC::$server->getUserManager()
								->get($UID);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	public function addWarning(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_Warning $e, $time
	) {
	}

	public static function setUser($which) {
		$userSession = \OC::$server->getUserSession();
		$userSession->setUser(
			\OC::$server->getUserManager()
						->get($which)
		);

		return $userSession->getUser()
						   ->getUID();
	}

	public static function currentUser() {
		$userSession = \OC::$server->getUserSession();
		return $userSession->getUser()
					->getUID();
	}

	public static function logout() {
		$userSession = \OC::$server->getUserSession();
		$userSession->setUser(null);
	}

	public static function listUsers() {
		return [
			self::ENV_TEST_OWNER1,
			self::ENV_TEST_OWNER2,
			self::ENV_TEST_OWNER3,
			self::ENV_TEST_ADMIN1,
			self::ENV_TEST_ADMIN2,
			self::ENV_TEST_ADMIN3,
			self::ENV_TEST_MODERATOR1,
			self::ENV_TEST_MODERATOR2,
			self::ENV_TEST_MODERATOR3,
			self::ENV_TEST_MEMBER1,
			self::ENV_TEST_MEMBER2,
			self::ENV_TEST_MEMBER3,
			self::ENV_TEST_USER1,
			self::ENV_TEST_USER2,
			self::ENV_TEST_USER3
		];
	}

	public static function listCircleTypes() {
		return [
			DeprecatedCircle::CIRCLES_PUBLIC,
			DeprecatedCircle::CIRCLES_CLOSED,
			DeprecatedCircle::CIRCLES_SECRET,
			DeprecatedCircle::CIRCLES_PERSONAL
		];
	}
}
