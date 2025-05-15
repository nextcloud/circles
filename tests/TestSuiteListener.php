<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests;

use OCA\Circles\Model\DeprecatedCircle;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
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
		Test $test, AssertionFailedError $e, float $time,
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

		$userManager = Server::get(IUserManager::class);
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
			$user = Server::get(IUserManager::class)
				->get($UID);
			if ($user !== null) {
				$user->delete();
			}
		}
	}

	public function addWarning(Test $test, Warning $e, float $time,
	): void {
	}

	public static function setUser($which) {
		$userSession = Server::get(IUserSession::class);
		$userSession->setUser(
			Server::get(IUserManager::class)
				->get($which)
		);

		return $userSession->getUser()->getUID();
	}

	public static function currentUser() {
		$userSession = Server::get(IUserSession::class);
		return $userSession->getUser()
			->getUID();
	}

	public static function logout() {
		$userSession = Server::get(IUserSession::class);
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
