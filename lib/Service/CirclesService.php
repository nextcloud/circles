<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleProviderRequest;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\FederatedLinksRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Util;

class CirclesService extends BaseService {

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var SharesRequest */
	private $sharesRequest;

	/** @var FederatedLinksRequest */
	private $federatedLinksRequest;

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
	 * @param IGroupManager $groupManager
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param SharesRequest $sharesRequest
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param EventsService $eventsService
	 * @param CircleProviderRequest $circleProviderRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		IGroupManager $groupManager,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		SharesRequest $sharesRequest,
		FederatedLinksRequest $federatedLinksRequest,
		EventsService $eventsService,
		CircleProviderRequest $circleProviderRequest,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->groupManager = $groupManager;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->sharesRequest = $sharesRequest;
		$this->federatedLinksRequest = $federatedLinksRequest;
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
	 * @return Circle
	 * @throws CircleTypeDisabledException
	 * @throws \Exception
	 */
	public function createCircle($type, $name) {
		$type = $this->convertTypeStringToBitValue($type);
		$type = (int)$type;

		if ($type === '') {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You need a specify a type of circle')
			);
		}

		if (!$this->configService->isCircleAllowed($type)) {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You cannot create this type of circle')
			);
		}

		$circle = new Circle($type, $name);

		try {
			$this->circlesRequest->createCircle($circle, $this->userId);
			$this->membersRequest->createMember($circle->getOwner());
			
			$owner = $circle->getOwner()->getDisplayName();
			$this->miscService->log("user $owner created circle $name");
		} catch (CircleAlreadyExistsException $e) {
			throw $e;
		}

		$this->eventsService->onCircleCreation($circle);

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
	 * @return Circle[]
	 * @throws CircleTypeDisabledException
	 * @throws Exception
	 */
	public function listCircles($userId, $type, $name = '', $level = 0) {
		$type = $this->convertTypeStringToBitValue($type);

		if ($userId === '') {
			throw new Exception('UserID cannot be null');
		}

		if (!$this->configService->isCircleAllowed((int)$type)) {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You cannot display this type of circle')
			);
		}

		$data = [];
		$result = $this->circlesRequest->getCircles($userId, $type, $name, $level);
		foreach ($result as $item) {
			$data[] = $item;
		}

		return $data;
	}


	/**
	 * returns details on circle and its members if this->userId is a member itself.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return Circle
	 * @throws \Exception
	 */
	public function detailsCircle($circleUniqueId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			if ($this->viewerIsAdmin()
				|| $circle->getHigherViewer()
						  ->isLevel(Member::LEVEL_MEMBER)
			) {
				$this->detailsCircleMembers($circle);
				$this->detailsCircleLinkedGroups($circle);
				$this->detailsCircleFederatedCircles($circle);
			}
		} catch (\Exception $e) {
			throw $e;
		}

		return $circle;
	}


	/**
	 * get the Members list and add the result to the Circle.
	 *
	 * @param Circle $circle
	 *
	 * @throws Exception
	 */
	private function detailsCircleMembers(Circle &$circle) {
		if ($this->viewerIsAdmin()) {
			$members = $this->membersRequest->forceGetMembers($circle->getUniqueId());
		} else {
			$members = $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		}

		$circle->setMembers($members);
	}


	/**
	 * get the Linked Group list and add the result to the Circle.
	 *
	 * @param Circle $circle
	 *
	 * @throws MemberDoesNotExistException
	 */
	private function detailsCircleLinkedGroups(Circle &$circle) {
		$groups = [];
		if ($this->configService->isLinkedGroupsAllowed()) {
			$groups =
				$this->membersRequest->getGroupsFromCircle(
					$circle->getUniqueId(), $circle->getHigherViewer()
				);
		}

		$circle->setGroups($groups);
	}


	/**
	 * get the Federated Circles list and add the result to the Circle.
	 *
	 * @param Circle $circle
	 */
	private function detailsCircleFederatedCircles(Circle &$circle) {
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
	 * save new settings if current user is admin.
	 *
	 * @param string $circleUniqueId
	 * @param array $settings
	 *
	 * @return Circle
	 * @throws \Exception
	 */
	public function settingsCircle($circleUniqueId, $settings) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$this->hasToBeOwner($circle->getHigherViewer());

			if (!$this->viewerIsAdmin()) {
				$settings['members_limit'] = $circle->getSetting('members_limit');
			}

			$ak = array_keys($settings);
			$changes = '';
			foreach ($ak AS $k) {
				if (trim($circle->getSetting($k)) !== trim($settings[$k])){
					$changes .=  ((empty($changes) ? '' : ' and ') . "$k to {$settings[$k]}");
				}
				$circle->setSetting($k, $settings[$k]);
			}

			$this->circlesRequest->updateCircle($circle, $this->userId);

			$this->eventsService->onSettingsChange($circle);
			
			$user = $this->getUser()->getDisplayName();
			$this->miscService->log("user $user updated circle $formerCircleName changing $changes");
		} catch (\Exception $e) {
			throw $e;
		}

		return $circle;
	}


	/**
	 * Join a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return null|Member
	 * @throws \Exception
	 */
	public function joinCircle($circleUniqueId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);

			$member = $this->membersRequest->getFreshNewMember(
				$circleUniqueId, $this->userId, Member::TYPE_USER
			);
			$formerStatus = $member->getStatus();
			$member->hasToBeAbleToJoinTheCircle();
			$this->checkThatCircleIsNotFull($circle);

			$member->joinCircle($circle->getType());
			$this->membersRequest->updateMember($member);

			$this->eventsService->onMemberNew($circle, $member);
			
			$circleName = $circle->getName();
			$circleType = $circle->getType();
			$memberName = $member->getDisplayName();
			if ($formerStatus == Member::STATUS_INVITED){
				$this->miscService->log("member $memberName accepted invitation to circle $circleName");
			} else if ($circle->getType() == Circle::CIRCLES_CLOSED) {
				$this->miscService->log("member $memberName requested to join circle $circleName");
			} else {
				$this->miscService->log("member $memberName joined circle $circleName");
			}
		} catch (\Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * Leave a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @return null|Member
	 * @throws \Exception
	 */
	public function leaveCircle($circleUniqueId) {

		$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
		$member = $circle->getViewer();

		$member->hasToBeMemberOrAlmost();
		$member->cantBeOwner();

		$circleName = $circle->getName();
		$formerStatus = $member->getStatus();
		$memberName = $member->getDisplayName();
		
		$this->eventsService->onMemberLeaving($circle, $member);

		$this->membersRequest->removeMember($member);
		$this->sharesRequest->removeSharesFromMember($member);

		if ($formerStatus == Member::STATUS_INVITED){
			$this->miscService->log("member $memberName refused invitation to circle $circleName");
		} else if ($circle->getType() == Circle::CIRCLES_CLOSED) {
			$this->miscService->log("member $memberName cancelled invitation from circle $circleName");
		} else {
			$this->miscService->log("member $memberName left circle $circleName");
		}

		return $member;
	}


	/**
	 * destroy a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @throws CircleDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws MemberIsNotOwnerException
	 */
	public function removeCircle($circleUniqueId) {

		$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);

		$this->hasToBeOwner($circle->getHigherViewer());

		$this->eventsService->onCircleDestruction($circle);

		$this->membersRequest->removeAllFromCircle($circleUniqueId);
		$this->circlesRequest->destroyCircle($circleUniqueId);

		$circleName = $circle->getName();
		$user = $this->getUser()->getDisplayName();
		$this->miscService->log("user $user destroyed circle $circleName");
	}


	/**
	 * @param $circleName
	 *
	 * @return Circle|null
	 * @throws CircleDoesNotExistException
	 */
	public function infoCircleByName($circleName) {
		return $this->circlesRequest->forceGetCircleByName($circleName);
	}


	/**
	 * When a user is removed.
	 * Before deleting a user from the cloud, we assign a new owner to his Circles.
	 * Remove the Circle if it has no admin.
	 *
	 * @param string $userId
	 */
	public function onUserRemoved($userId) {
		$circles = $this->circlesRequest->getCircles($userId, 0, '', Member::LEVEL_OWNER);

		foreach ($circles as $circle) {

			$members =
				$this->membersRequest->forceGetMembers($circle->getUniqueId(), Member::LEVEL_ADMIN);

			if (sizeof($members) === 1) {
				$this->circlesRequest->destroyCircle($circle->getUniqueId());
				continue;
			}

			$this->switchOlderAdminToOwner($circle, $members);
		}
	}


	/**
	 * switchOlderAdminToOwner();
	 *
	 * @param Circle $circle
	 * @param Member[] $members
	 */
	private function switchOlderAdminToOwner(Circle $circle, $members) {

		foreach ($members as $member) {
			if ($member->getLevel() === Member::LEVEL_ADMIN) {
				$member->setLevel(Member::LEVEL_OWNER);
				$this->membersRequest->updateMember($member);
				$this->eventsService->onMemberOwner($circle, $member);

				return;
			}
		}

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
			'personal' => Circle::CIRCLES_PERSONAL,
			'secret'   => Circle::CIRCLES_SECRET,
			'closed'   => Circle::CIRCLES_CLOSED,
			'public'   => Circle::CIRCLES_PUBLIC,
			'all'      => Circle::CIRCLES_ALL
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

		$urlGen = \OC::$server->getURLGenerator();
		switch ($type) {
			case Circle::CIRCLES_PERSONAL:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_NAME, 'personal' . $ext)
				);
			case Circle::CIRCLES_CLOSED:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_NAME, 'closed' . $ext)
				);
			case Circle::CIRCLES_SECRET:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_NAME, 'secret' . $ext)
				);
			case Circle::CIRCLES_PUBLIC:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath(Application::APP_NAME, 'public' . $ext)
				);
		}

		return $urlGen->getAbsoluteURL(
			$urlGen->imagePath(Application::APP_NAME, 'black_circle' . $ext)
		);
	}


	/**
	 * @param string $circleUniqueIds
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	public function getFilesForCircles($circleUniqueIds, $limit = -1, $offset = 0) {
		if (!is_array($circleUniqueIds)) {
			$circleUniqueIds = [$circleUniqueIds];
		}

		$objectIds = $this->circleProviderRequest->getFilesForCircles(
			$this->userId, $circleUniqueIds, $limit, $offset
		);

		return $objectIds;
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws MembersLimitException
	 */
	public function checkThatCircleIsNotFull(Circle $circle) {

		$members = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::STATUS_MEMBER, true
		);

		$limit = $circle->getSetting('members_limit');
		if ($limit === -1) {
			return;
		}
		if ($limit === 0 || $limit === '' || $limit === null) {
			$limit = $this->configService->getAppValue(ConfigService::CIRCLES_MEMBERS_LIMIT);
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
	public function viewerIsAdmin() {
		if ($this->userId === '') {
			return false;
		}

		return ($this->groupManager->isAdmin($this->userId));
	}


	/**
	 * should be moved.
	 *
	 * @param Member $member
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function hasToBeOwner(Member $member) {
		if (!$this->groupManager->isAdmin($this->userId)
			&& $member->getLevel() < Member::LEVEL_OWNER) {
			throw new MemberIsNotOwnerException(
				$this->l10n->t('This member is not the owner of the circle')
			);
		}
	}


	/**
	 * should be moved.
	 *
	 * @param Member $member
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function hasToBeAdmin(Member $member) {
		if (!$this->groupManager->isAdmin($member->getUserId())
			&& $member->getLevel() < Member::LEVEL_ADMIN) {
			throw new MemberIsNotOwnerException(
				$this->l10n->t('This member is not an admin of the circle')
			);
		}
	}
}