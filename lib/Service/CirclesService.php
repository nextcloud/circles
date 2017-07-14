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


use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IL10N;

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

	/** @var EventsService */
	private $eventsService;

	/** @var MiscService */
	private $miscService;


	/**
	 * CirclesService constructor.
	 *
	 * @param $userId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param EventsService $eventsService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		EventsService $eventsService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->eventsService = $eventsService;
		$this->miscService = $miscService;
	}


	/**
	 * Create circle using this->userId as owner
	 *
	 * @param int $type
	 * @param string $name
	 *
	 * @return Circle
	 * @throws CircleTypeDisabledException
	 * @throws \Exception
	 */
	public function createCircle($type, $name) {
		self::convertTypeStringToBitValue($type);

		if ($type === "") {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You need a specify a type of circle')
			);
		}

		if (!$this->configService->isCircleAllowed($type)) {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You cannot create this type of circle')
			);
		}

		$circle = new Circle($this->l10n, $type, $name);

		try {
			$this->circlesRequest->createCircle($circle, $this->userId);
			$this->membersRequest->createMember($circle->getOwner());
		} catch (\Exception $e) {
			$this->circlesRequest->destroyCircle($circle->getId());
			throw $e;
		}

		$this->eventsService->onCircleCreation($circle);

		return $circle;
	}


	/**
	 * list Circles depends on type (or all) and name (parts) and minimum level.
	 *
	 * @param $type
	 * @param string $name
	 * @param int $level
	 *
	 * @return Circle[]
	 * @throws CircleTypeDisabledException
	 */
	public function listCircles($type, $name = '', $level = 0) {
		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			throw new CircleTypeDisabledException(
				$this->l10n->t('You cannot display this type of circle')
			);
		}

		$data = [];
		$result = $this->circlesRequest->getCircles($this->userId, $type, $name, $level);
		foreach ($result as $item) {
			$data[] = $item;
		}

		return $data;
	}


	/**
	 * returns details on circle and its members if this->userId is a member itself.
	 *
	 * @param $circleId
	 *
	 * @return Circle
	 * @throws \Exception
	]	 */
	public function detailsCircle($circleId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleId, $this->userId);
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
			$this->membersRequest->getMembers($circle->getId(), $circle->getHigherViewer());

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
				$this->membersRequest->getGroups($circle->getId(), $circle->getHigherViewer());
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
				$links = $this->circlesRequest->getLinksFromCircle($circle->getId());
			}
		} catch (FederatedCircleNotAllowedException $e) {
		}

		$circle->setLinks($links);
	}


	/**
	 * save new settings if current user is admin.
	 *
	 * @param $circleId
	 * @param array $settings
	 *
	 * @return Circle
	 * @throws \Exception
	 */
	public function settingsCircle($circleId, $settings) {

		try {
			$circle = $this->circlesRequest->getCircle($circleId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeOwner();

			$ak = array_keys($settings);
			foreach ($ak AS $k) {
				$circle->setSetting($k, $settings[$k]);
			}

			$this->circlesRequest->updateCircle($circle);
		} catch (\Exception $e) {
			throw $e;
		}

		return $circle;
	}


	/**
	 * Join a circle.
	 *
	 * @param $circleId
	 *
	 * @return null|Member
	 * @throws \Exception
	 */
	public function joinCircle($circleId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleId, $this->userId);

			try {
				$member = $this->membersRequest->forceGetMember($circle->getId(), $this->userId);
			} catch (MemberDoesNotExistException $m) {
				$member = new Member($this->l10n, $this->userId, $circle->getId());
				$this->membersRequest->createMember($member);
			}

			$member->hasToBeAbleToJoinTheCircle();
			$member->joinCircle($circle->getType());
			$this->membersRequest->updateMember($member);
			$this->eventsService->onMemberNew($circle, $member);
		} catch (\Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * Leave a circle.
	 *
	 * @param $circleId
	 *
	 * @return null|Member
	 * @throws \Exception
	 */
	public function leaveCircle($circleId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleId, $this->userId);
			$member = $circle->getViewer();

			if (!$member->isAlmostMember()) {
				$member->hasToBeMember();
			}

			$member->cantBeOwner();

			$member->setStatus(Member::STATUS_NONMEMBER);
			$member->setLevel(Member::LEVEL_NONE);
			$this->membersRequest->updateMember($member);

			$this->eventsService->onMemberLeaving($circle, $member);
		} catch (\Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * destroy a circle.
	 *
	 * @param int $circleId
	 *
	 * @throws MemberIsNotOwnerException
	 */
	public function removeCircle($circleId) {

		try {
			$circle = $this->circlesRequest->getCircle($circleId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeOwner();

			$this->eventsService->onCircleDestruction($circle);

			$this->membersRequest->removeAllFromCircle($circleId);
			$this->circlesRequest->destroyCircle($circleId);

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
	 * Convert a Type in String to its Bit Value
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public static function convertTypeStringToBitValue(& $type) {
		if (strtolower($type) === 'personal') {
			$type = Circle::CIRCLES_PERSONAL;
		}
		if (strtolower($type) === 'hidden') {
			$type = Circle::CIRCLES_HIDDEN;
		}
		if (strtolower($type) === 'private') {
			$type = Circle::CIRCLES_PRIVATE;
		}
		if (strtolower($type) === 'public') {
			$type = Circle::CIRCLES_PUBLIC;
		}
		if (strtolower($type) === 'all') {
			$type = Circle::CIRCLES_ALL;
		}

		return 0;
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
					$urlGen->imagePath('circles', 'personal' . $ext)
				);
			case Circle::CIRCLES_PRIVATE:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath('circles', 'private' . $ext)
				);
			case Circle::CIRCLES_HIDDEN:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath('circles', 'hidden' . $ext)
				);
			case Circle::CIRCLES_PUBLIC:
				return $urlGen->getAbsoluteURL(
					$urlGen->imagePath('circles', 'public' . $ext)
				);
		}

		return $urlGen->getAbsoluteURL($urlGen->imagePath('circles', 'black_circle' . $ext));
	}

}