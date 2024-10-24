<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
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

namespace OCA\Circles\Api\v1;

use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareWrapperService;

class Circles {
	public const API_VERSION = [0, 10, 0];

	// Expose circle and member constants via API
	public const CIRCLES_PERSONAL = 1;
	public const CIRCLES_SECRET = 2;
	public const CIRCLES_CLOSED = 4;
	public const CIRCLES_PUBLIC = 8;
	public const CIRCLES_ALL = 15;

	public const TYPE_USER = Member::TYPE_USER;
	public const TYPE_GROUP = Member::TYPE_GROUP;
	public const TYPE_MAIL = Member::TYPE_MAIL;
	public const TYPE_CONTACT = Member::TYPE_CONTACT;

	public const LEVEL_NONE = Member::LEVEL_NONE;
	public const LEVEL_MEMBER = Member::LEVEL_MEMBER;
	public const LEVEL_MODERATOR = Member::LEVEL_MODERATOR;
	public const LEVEL_ADMIN = Member::LEVEL_ADMIN;
	public const LEVEL_OWNER = Member::LEVEL_OWNER;


	/**
	 * Circles::listCircles();
	 *
	 * This function list all circles fitting a search regarding its name and the level and the
	 * rights from the current user. In case of Secret circle, name needs to be complete so the
	 * circle is included in the list (or if the current user is the owner)
	 *
	 * example: Circles::listCircles(Circles::CIRCLES_ALL, '', 8, callback); will returns all
	 * circles when the current user is at least an Admin.
	 *
	 * @param mixed $type
	 * @param string $name
	 * @param int $level
	 * @param string $userId
	 * @param bool $forceAll
	 *
	 * @return Circle[]
	 */
	public static function listCircles($type, $name = '', $level = 0, $userId = '', $forceAll = false) {
		/** @var FederatedUserService $federatedUserService */
		$federatedUserService = \OC::$server->get(FederatedUserService::class);

		$personalCircle = false;
		if ($forceAll) {
			$personalCircle = true;
		}

		if ($userId === '') {
			$federatedUserService->initCurrentUser();
		} else {
			$federatedUserService->setLocalCurrentUserId($userId);
		}

		/** @var CircleService $circleService */
		$circleService = \OC::$server->get(CircleService::class);

		$probe = new CircleProbe();
		$probe->includePersonalCircles($personalCircle);
		$probe->filterHiddenCircles();

		return $circleService->getCircles($probe);
	}


	/**
	 * @param string $userId
	 * @param bool $forceAll
	 *
	 * @return Circle[]
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 *
	 * @deprecated - used by apps/dav/lib/Connector/Sabre/Principal.php
	 *
	 * Circles::joinedCircles();
	 *
	 * Return all the circle the current user is a member.
	 */
	public static function joinedCircles($userId = '', $forceAll = false) {
		/** @var FederatedUserService $federatedUserService */
		$federatedUserService = \OC::$server->get(FederatedUserService::class);

		$personalCircle = false;
		if ($forceAll) {
			$personalCircle = true;
		}

		if ($userId === '') {
			$federatedUserService->initCurrentUser();
		} else {
			$federatedUserService->setLocalCurrentUserId($userId);
		}

		/** @var CircleService $circleService */
		$circleService = \OC::$server->get(CircleService::class);

		$probe = new CircleProbe();
		$probe->mustBeMember();
		$probe->includePersonalCircles($personalCircle);
		$probe->filterHiddenCircles();

		return $circleService->getCircles($probe);
	}


	/**
	 * @param string $circleUniqueId
	 * @param bool $forceAll
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 *
	 * @deprecated - used by apps/dav/lib/Connector/Sabre/Principal.php
	 *             - used by apps/files_sharing/lib/Controller/ShareAPIController.php
	 *             - used by lib/private/Share20/Manager.php
	 *
	 * Circles::detailsCircle();
	 *
	 * WARNING - This function is called by the core - WARNING
	 *                 Do not change it
	 *
	 * Returns details on the circle. If the current user is a member, the members list will be
	 * return as well.
	 *
	 */
	public static function detailsCircle(string $circleUniqueId, bool $forceAll = false): Circle {
		/** @var FederatedUserService $federatedUserService */
		$federatedUserService = \OC::$server->get(FederatedUserService::class);
		if ($forceAll || \OC::$CLI) {
			$federatedUserService->bypassCurrentUserCondition(true);
		} else {
			$federatedUserService->initCurrentUser();
		}

		/** @var CircleService $circleService */
		$circleService = \OC::$server->get(CircleService::class);

		return $circleService->getCircle($circleUniqueId);
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $ident
	 * @param int $type
	 * @param bool $forceAll
	 *
	 * @return Membership
	 *
	 * @deprecated - used by apps/files_sharing/lib/Controller/ShareAPIController.php
	 *
	 * Circles::getMember();
	 *
	 * This function will return information on a member of the circle. Current user need at least
	 * to be Member.
	 *
	 */
	public static function getMember($circleUniqueId, $ident, $type, $forceAll = false) {
		/** @var CirclesManager $circlesManager */
		$circlesManager = \OC::$server->get(CirclesManager::class);
		$federatedUser = $circlesManager->getFederatedUser($ident, $type);

		return $circlesManager->getLink($circleUniqueId, $federatedUser->getSingleId());
	}


	/**
	 * @param array $circleUniqueIds
	 *
	 * @return int[] array of object ids or empty array if none found
	 *
	 * @deprecated - used by apps/dav/lib/Connector/Sabre/FilesReportPlugin.php
	 *
	 * Get a list of objects which are shred with $circleUniqueId.
	 *
	 * @since 0.14.0
	 *
	 */
	public static function getFilesForCircles(array $circleUniqueIds): array {
		try {
			$circleService = \OC::$server->get(CircleService::class);
			$federatedUserService = \OC::$server->get(FederatedUserService::class);
			$shareWrapperService = \OC::$server->get(ShareWrapperService::class);

			$federatedUserService->initCurrentUser();
		} catch (\Exception $e) {
			return [];
		}

		$result = [];
		foreach ($circleUniqueIds as $uniqueId) {
			try {
				$circleService->getCircle($uniqueId); // checking current user have access to said circle
				$files = array_map(
					function (ShareWrapper $wrapper): int {
						return $wrapper->getFileSource();
					}, $shareWrapperService->getSharesToCircle($uniqueId)
				);
			} catch (\Exception $e) {
				$files = [];
			}

			$result = array_merge($files, $result);
		}

		return array_values(array_unique($result));
	}
}
