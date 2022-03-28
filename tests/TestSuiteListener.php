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
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Warning;
use Throwable;

class Env implements TestListener {
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

	public function addError(Test $test, Throwable $e, float $time): void {
	}

	public function addFailure(
		Test $test, AssertionFailedError $e, float $time
	): void {
	}

	public function addIncompleteTest(Test $test, Throwable $e, float $time): void {
	}

	public function addRiskyTest(Test $test, Throwable $e, float $time): void {
	}

	public function addSkippedTest(Test $test, Throwable $e, float $time): void {
	}

	public function startTest(Test $test): void {
	}

	public function endTest(Test $test, float $time): void {
	}

	public function startTestSuite(TestSuite $suite): void {
		if ($suite->getName() !== 'OCA\Circles\Tests\Api\CirclesTest') {
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

	public function endTestSuite(TestSuite $suite): void {
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

	public function addWarning(Test $test, Warning $e, float $time
	): void {
	}

	public static function setUser($which) {
		$userSession = \OC::$server->getUserSession();
		$userSession->setUser(
			\OC::$server->getUserManager()
						->get($which)
		);

		return $userSession->getUser()->getUID();
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
