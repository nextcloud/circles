<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


class Env implements \PHPUnit_Framework_TestListener {


	const ENV_TEST_OWNER1 = '_test_circles_owner1';
	const ENV_TEST_OWNER2 = '_test_circles_owner2';

	const ENV_TEST_USER1 = '_test_circles_user1';
	const ENV_TEST_USER2 = '_test_circles_user2';

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
		$this->users = [
			self::ENV_TEST_OWNER1,
			self::ENV_TEST_OWNER2,
			self::ENV_TEST_USER1,
			self::ENV_TEST_USER2
		];

		foreach ($this->users AS $UID) {
			if ($userManager->userExists($UID) === false) {
				$userManager->createUser($UID, $UID);
			}
		}
	}

	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		if ($suite->getName() !== '.') {
			return;
		}

		foreach ($this->users AS $UID) {
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

	public static function logout() {
		$userSession = \OC::$server->getUserSession();
		$userSession->setUser(null);
	}

}


