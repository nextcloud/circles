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

namespace OCA\Circles\Service;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleProviderRequest;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Db\FileSharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserSession;

/**
 * Class CirclesService
 *
 * @deprecated
 * @package OCA\Circles\Service
 */
class CirclesService {
	use TArrayTools;


	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var IGroupManager */
	private $groupManager;

	/** @var MembersService */
	private $membersService;

	/** @var ConfigService */
	private $configService;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var FileSharesRequest */
	private $fileSharesRequest;

	/** @var FederatedLinksRequest */
	private $federatedLinksRequest;

	/** @var GSUpstreamService */
	private $gsUpstreamService;

	/** @var EventsService */
	private $eventsService;

	/** @var CircleProviderRequest */
	private $circleProviderRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * CirclesService constructor.
	 *
	 * @param string $userId
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param MembersService $membersService
	 * @param ConfigService $configService
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param TokensRequest $tokensRequest
	 * @param FileSharesRequest $fileSharesRequest
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param GSUpstreamService $gsUpstreamService
	 * @param EventsService $eventsService
	 * @param CircleProviderRequest $circleProviderRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		IUserSession $userSession,
		IGroupManager $groupManager,
		MembersService $membersService,
		ConfigService $configService,
		DeprecatedCirclesRequest $circlesRequest,
		DeprecatedMembersRequest $membersRequest,
		TokensRequest $tokensRequest,
		FileSharesRequest $fileSharesRequest,
		FederatedLinksRequest $federatedLinksRequest,
		GSUpstreamService $gsUpstreamService,
		EventsService $eventsService,
		CircleProviderRequest $circleProviderRequest,
		MiscService $miscService
	) {
		if ($userId === null) {
			$user = $userSession->getUser();
			if ($user !== null) {
				$userId = $user->getUID();
			}
		}

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->groupManager = $groupManager;
		$this->membersService = $membersService;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->tokensRequest = $tokensRequest;
		$this->fileSharesRequest = $fileSharesRequest;
		$this->federatedLinksRequest = $federatedLinksRequest;
		$this->gsUpstreamService = $gsUpstreamService;
		$this->eventsService = $eventsService;
		$this->circleProviderRequest = $circleProviderRequest;
		$this->miscService = $miscService;
	}


	/**
	 * Create circle using this->userId as owner
	 *
	 * @param int|string $type
	 * @param string $name
	 *
	 * @param string $ownerId
	 *
	 * @return DeprecatedCircle
	 * @throws CircleAlreadyExistsException
	 * @throws CircleTypeDisabledException
	 * @throws Exception
	 */
	public function createCircle($type, $name, string $ownerId = '') {
		$type = $this->convertTypeStringToBitValue($type);
		$type = (int)$type;

		if ($type === '') {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You need a specify a type of circle')
			);
		}

//		if (!$this->configService->isCircleAllowed($type)) {
//			throw new CircleTypeDisabledException(
//				$this->l10n->t('You cannot create this type of circle')
//			);
//		}

		$circle = new DeprecatedCircle($type, $name);
		if ($ownerId === '') {
			$ownerId = $this->userId;
		}

		if (!$this->circlesRequest->isCircleUnique($circle, $ownerId)) {
			throw new CircleAlreadyExistsException(
				$this->l10n->t('A circle with that name exists')
			);
		}

		$circle->generateUniqueId();

		$owner = new DeprecatedMember($ownerId, DeprecatedMember::TYPE_USER);
		$owner->setCircleId($circle->getUniqueId())
			  ->setLevel(DeprecatedMember::LEVEL_OWNER)
			  ->setStatus(DeprecatedMember::STATUS_MEMBER);
		$this->membersService->updateCachedName($owner);

		$circle->setOwner($owner)
			   ->setViewer($owner);

		$event = new GSEvent(GSEvent::CIRCLE_CREATE, true);
		$event->setDeprecatedCircle($circle);
		$this->gsUpstreamService->newEvent($event);

		return $circle;
	}


	/**
	 * list Circles depends on type (or all) and name (parts) and minimum level.
	 *
	 * @param string $userId
	 * @param mixed $type
	 * @param string $name
	 * @param int $level
	 *
	 * @param bool $forceAll
	 *
	 * @return DeprecatedCircle[]
	 * @throws CircleTypeDisabledException
	 * @throws Exception
	 */
	public function listCircles($userId, $type, $name = '', $level = 0, $forceAll = false) {
		$type = $this->convertTypeStringToBitValue($type);

		if ($userId === '') {
			throw new Exception('UserID cannot be null');
		}

//		if (!$this->configService->isCircleAllowed((int)$type)) {
//			throw new CircleTypeDisabledException(
//				$this->l10n->t('You cannot display this type of circle')
//			);
//		}

		$data = [];
		$result = $this->circlesRequest->getCircles($userId, $type, $name, $level, $forceAll);
		foreach ($result as $item) {
			$data[] = $item;
		}

		return $data;
	}


	/**
	 * returns details on circle and its members if this->userId is a member itself.
	 *
	 * @param string $circleUniqueId
	 * @param bool $forceAll
	 *
	 * @return DeprecatedCircle
	 * @throws Exception
	 */
	public function detailsCircle($circleUniqueId, $forceAll = false) {
		try {
			if (!$forceAll) {
				$circle = $this->circlesRequest->getCircle(
					$circleUniqueId, $this->userId, Member::TYPE_USER, '', $forceAll
				);
			} else {
				$circle = $this->circlesRequest->getCircleFromUniqueId($circleUniqueId);
			}
			if ($forceAll === true || $this->viewerIsAdmin()
				|| $circle->getHigherViewer()
						  ->isLevel(Member::LEVEL_MEMBER)
			) {
				$this->detailsCircleMembers($circle, $forceAll);
				$this->detailsCircleLinkedGroups($circle);
				$this->detailsCircleFederatedCircles($circle);
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $circle;
	}


	/**
	 * get the Members list and add the result to the Circle.
	 *
	 * @param DeprecatedCircle $circle
	 *
	 * @throws Exception
	 */
	private function detailsCircleMembers(Circle $circle, $forceAll = false) {
		if ($forceAll || $this->viewerIsAdmin()) {
			$members = $this->membersRequest->forceGetMembers($circle->getUniqueId(), 0);
		} else {
			$members = $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		}

		$circle->setMembers($members);
	}


	/**
	 * // TODO - check this on GS setup
	 * get the Linked Group list and add the result to the Circle.
	 *
	 * @param DeprecatedCircle $circle
	 *
	 * @throws GSStatusException
	 */
	private function detailsCircleLinkedGroups(DeprecatedCircle $circle) {
//		$groups = [];
//		if ($this->configService->isLinkedGroupsAllowed()) {
//			$groups =
//				$this->membersRequest->getGroupsFromCircle(
//					$circle->getUniqueId(), $circle->getHigherViewer()
//				);
//		}
//
//		$circle->setGroups($groups);
	}


	/**
	 * get the Federated Circles list and add the result to the Circle.
	 *
	 * @param DeprecatedCircle $circle
	 */
	private function detailsCircleFederatedCircles(DeprecatedCircle $circle) {
		$links = [];

		try {
			if ($this->configService->isFederatedCirclesAllowed()) {
				$circle->hasToBeFederated();
				$links = $this->federatedLinksRequest->getLinksFromCircle($circle->getUniqueId());
			}
		} catch (FederatedCircleNotAllowedException $e) {
		}

		$circle->setLinks($links);
	}


	/**
	 * @param DeprecatedCircle $circle
	 */
	public function updatePasswordOnShares(DeprecatedCircle $circle) {
		$this->tokensRequest->updateSinglePassword($circle->getUniqueId(), $circle->getPasswordSingle());
	}


	/**
	 * Join a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return null|DeprecatedMember
	 * @throws Exception
	 */
	public function joinCircle($circleUniqueId): DeprecatedMember {
		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$member = $this->membersRequest->getFreshNewMember(
				$circleUniqueId, $this->userId, DeprecatedMember::TYPE_USER, ''
			);

			$this->membersService->updateCachedName($member);

			$event = new GSEvent(GSEvent::MEMBER_JOIN);
			$event->setDeprecatedCircle($circle);
			$event->setMember($member);
			$this->gsUpstreamService->newEvent($event);
		} catch (Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * Leave a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return null|DeprecatedMember
	 * @throws Exception
	 */
	public function leaveCircle($circleUniqueId) {
		$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
		$member = $circle->getViewer();

		$event = new GSEvent(GSEvent::MEMBER_LEAVE);
		$event->setDeprecatedCircle($circle);
		$event->setMember($member);
		$this->gsUpstreamService->newEvent($event);

		return $member;
	}


	/**
	 * @param $circleName
	 *
	 * @return DeprecatedCircle|null
	 * @throws CircleDoesNotExistException
	 */
	public function infoCircleByName($circleName) {
		return $this->circlesRequest->forceGetCircleByName($circleName);
	}


	/**
	 * Convert a Type in String to its Bit Value
	 *
	 * @param string $type
	 *
	 * @return int|mixed
	 */
	public function convertTypeStringToBitValue($type) {
		$strings = [
			'personal' => DeprecatedCircle::CIRCLES_PERSONAL,
			'secret' => DeprecatedCircle::CIRCLES_SECRET,
			'closed' => DeprecatedCircle::CIRCLES_CLOSED,
			'public' => DeprecatedCircle::CIRCLES_PUBLIC,
			'all' => DeprecatedCircle::CIRCLES_ALL
		];

		if (!key_exists(strtolower($type), $strings)) {
			return $type;
		}

		return $strings[strtolower($type)];
	}


	/**
	 * getCircleIcon()
	 *
	 * Return the right imagePath for a type of circle.
	 *
	 * @param string $type
	 * @param bool $png
	 *
	 * @return string
	 */
	public static function getCircleIcon($type, $png = false) {
		$ext = '.svg';
		if ($png === true) {
			$ext = '.png';
		}

		$urlGen = OC::$server->getURLGenerator();
		switch ($type) {
			case DeprecatedCircle::CIRCLES_PERSONAL:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_ID, 'personal' . $ext)
				);
			case DeprecatedCircle::CIRCLES_CLOSED:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_ID, 'closed' . $ext)
				);
			case DeprecatedCircle::CIRCLES_SECRET:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_ID, 'secret' . $ext)
				);
			case DeprecatedCircle::CIRCLES_PUBLIC:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_ID, 'circles' . $ext)
				);
		}

		return $urlGen->getAbsoluteURL(
			$urlGen->imagePath(Application::APP_ID, 'circles' . $ext)
		);
	}


	/**
	 * @param string $circleUniqueIds
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 * @throws GSStatusException
	 */
	public function getFilesForCircles($circleUniqueIds, $limit = -1, $offset = 0) {
		if (!is_array($circleUniqueIds)) {
			$circleUniqueIds = [$circleUniqueIds];
		}

		return $this->circleProviderRequest->getFilesForCircles(
			$this->userId, $circleUniqueIds, $limit, $offset
		);
	}


	/**
	 * @param DeprecatedCircle $circle
	 *
	 * @throws MembersLimitException
	 */
	public function checkThatCircleIsNotFull(DeprecatedCircle $circle) {
		$members =
			$this->membersRequest->forceGetMembers(
				$circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, 0, true
			);

		$limit = (int)$circle->getSetting('members_limit');
		if ($limit === -1) {
			return;
		}
		if ($limit === 0) {
			$limit = $this->configService->getAppValue(ConfigService::MEMBERS_LIMIT);
		}

		if (sizeof($members) >= $limit) {
			throw new MembersLimitException(
				'This circle already reach its limit on the number of members'
			);
		}
	}

	/**
	 * @return bool
	 */
	public function viewerIsAdmin(): bool {
		if ($this->userId === '') {
			return false;
		}

		return ($this->groupManager->isAdmin($this->userId));
	}


	/**
	 * should be moved.
	 *
	 * @param DeprecatedMember $member
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function hasToBeOwner(DeprecatedMember $member) {
		if (!$this->groupManager->isAdmin($this->userId)
			&& $member->getLevel() < DeprecatedMember::LEVEL_OWNER) {
			throw new MemberIsNotOwnerException(
				$this->l10n->t('This member is not the owner of the circle')
			);
		}
	}


	/**
	 * should be moved.
	 *
	 * @param DeprecatedMember $member
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function hasToBeAdmin(DeprecatedMember $member) {
		if (!$this->groupManager->isAdmin($member->getUserId())
			&& $member->getLevel() < DeprecatedMember::LEVEL_ADMIN) {
			throw new MemberIsNotOwnerException(
				$this->l10n->t('This member is not an admin of the circle')
			);
		}
	}
}
