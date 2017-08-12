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

namespace OCA\Circles\Service;

use OC\User\NoUserException;
use OCP\ILogger;
use OCP\IUserManager;

class MiscService {

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $appName;

	/** @var IUserManager */
	private $userManager;

	public function __construct(ILogger $logger, $appName, IUserManager $userManager) {
		$this->logger = $logger;
		$this->appName = $appName;
		$this->userManager = $userManager;
	}

	public function log($message, $level = 2) {
		$data = array(
			'app'   => $this->appName,
			'level' => $level
		);

		$this->logger->log($level, $message, $data);
	}



	/**
	 * return the real userId, with its real case
	 *
	 * @param $userId
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public function getRealUserId($userId) {
		if (!$this->userManager->userExists($userId)) {
			throw new NoUserException();
		}

		return $this->userManager->get($userId)
								 ->getUID();
	}



	/**
	 * return Display Name if user exists and display name exists.
	 * returns Exception if user does not exist.
	 *
	 * However, with noException set to true, will return userId even if user does not exist
	 *
	 * @param $userId
	 * @param bool $noException
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public static function staticGetDisplayName($userId, $noException = false) {
		$user = \OC::$server->getUserManager()
							->get($userId);
		if ($user === null) {
			if ($noException) {
				return $userId;
			} else {
				throw new NoUserException();
			}
		}

		return $user->getDisplayName();
	}


	/**
	 * return Display Name if user exists and display name exists.
	 * returns Exception if user does not exist.
	 *
	 * However, with noException set to true, will return userId even if user does not exist
	 *
	 * @param $userId
	 * @param bool $noException
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public function getDisplayName($userId, $noException = false) {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			if ($noException) {
				return $userId;
			} else {
				throw new NoUserException();
			}
		}

		return $user->getDisplayName();
	}
}

