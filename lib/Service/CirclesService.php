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
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\ShareByCircleProvider;
use OCP\IL10N;
use OCP\Util;

class CirclesService {

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

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
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param FederatedLinksRequest $federatedLinksRequest
	 * @param EventsService $eventsService
	 * @param CircleProviderRequest $circleProviderRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		FederatedLinksRequest $federatedLinksRequest,
		EventsService $eventsService,
		CircleProviderRequest $circleProviderRequest,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
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
			
			if ($this->configService->isAuditEnabled()){
				Util::emitHook('OCA\Circles', 'post_createCircle', ['circle' => $name]);
			}
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
	]	 */
	public function detailsCircle($circleUniqueId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			if ($circle->getHigherViewer()
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
	 */
	private function detailsCircleMembers(Circle &$circle) {
		$members =
			$this->membersRequest->getMembers($circle->getUniqueId(), $circle->getHigherViewer());

		$circle->setMembers($members);
	}


	/**
	 * get the Linked Group list and add the result to the Circle.
	 *
	 * @param Circle $circle
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
			$formerCircle = $circle->getName();
			$circle->getHigherViewer()
				   ->hasToBeOwner();

			$ak = array_keys($settings);
			foreach ($ak AS $k) {
				$circle->setSetting($k, $settings[$k]);
			}

			$this->circlesRequest->updateCircle($circle, $this->userId);

			$this->eventsService->onSettingsChange($circle);
			
			if ($this->configService->isAuditEnabled()){
				$settings['former_name']= $formerCircle;
				Util::emitHook('OCA\Circles', 'post_updateCircle', $settings);
			}
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
			$member->joinCircle($circle->getType());
			$this->membersRequest->updateMember($member);

			$this->eventsService->onMemberNew($circle, $member);
			
			if ($this->configService->isAuditEnabled()){
				$settings['member'] = $member->getDisplayName();
				$settings['circle'] = $circle->getName();
				$settings['formerStatus'] = $formerStatus;
				$settings['type'] = $circle->getType();
				Util::emitHook('OCA\Circles', 'post_joinMember', $settings);
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

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$member = $circle->getViewer();

			$member->hasToBeMemberOrAlmost();
			$member->cantBeOwner();

			$this->eventsService->onMemberLeaving($circle, $member);

			$formerStatus = $member->getStatus();
			$member->setStatus(Member::STATUS_NONMEMBER);
			$member->setLevel(Member::LEVEL_NONE);
			$this->membersRequest->updateMember($member);

			if ($this->configService->isAuditEnabled()){
				Util::emitHook('OCA\Circles', 'post_leftMember', [
					'circle' => $circle->getName(),
					'member' => $member->getDisplayName(),
					'formerStatus' => $formerStatus,
					'type' => $circle->getType()
				]);
			}
		} catch (\Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * destroy a circle.
	 *
	 * @param string $circleUniqueId
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function removeCircle($circleUniqueId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeOwner();

			$this->eventsService->onCircleDestruction($circle);

			$this->membersRequest->removeAllFromCircle($circleUniqueId);
			$this->circlesRequest->destroyCircle($circleUniqueId);
			
			if ($this->configService->isAuditEnabled()){
				Util::emitHook('OCA\Circles', 'post_destroyCircle', ['circle' => $circle->getName()]);
			}
		} catch (MemberIsNotOwnerException $e) {
			throw $e;
		}
	}


	/**
	 * @param $circleName
	 *
	 * @return Circle|null
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
	 * @param Member[] $members
	 */
	private function switchOlderAdminToOwner($circle, $members) {

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

		return $urlGen->getAbsoluteURL($urlGen->imagePath(Application::APP_NAME, 'black_circle' . $ext));
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

}