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


use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleCreationException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailable;
use OCA\Circles\Exceptions\MemberCantJoinPersonalCircle;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;
use OCP\IL10N;

class CirclesService {

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var DatabaseService */
	private $dbService;

	/** @var MiscService */
	private $miscService;


	/**
	 * CirclesService constructor.
	 *
	 * @param $userId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param DatabaseService $databaseService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		DatabaseService $databaseService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->dbService = $databaseService;
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

		if (!$this->configService->isCircleAllowed($type)) {
			throw new CircleTypeDisabledException();
		}

		$owner = new Member();
		$owner->setUserId($this->userId)
			  ->setStatus(Member::STATUS_MEMBER);

		$circle = new Circle();
		$circle->setName($name)
			   ->setType($type)
			   ->setMembers([$owner]);

		try {
			$this->dbService->getCirclesMapper()
							->create($circle, $owner);
			$this->dbService->getMembersMapper()
							->add($owner);

		} catch (\Exception $e) {
			$this->dbService->getCirclesMapper()
							->destroy($circle);
			throw $e;
		}

		return $circle;
	}


	/**
	 * list Circles depends on type (or all) and name (parts) and minimum level.
	 *
	 * @param $type
	 * @param string $name
	 * @param int $level
	 *
	 * @return array
	 * @throws CircleTypeDisabledException
	 */
	public function listCircles($type, $name = '', $level = 0) {

		if (!$this->configService->isCircleAllowed((int)$type)) {
			throw new CircleTypeDisabledException();
		}

		$result = $this->dbService->getCirclesMapper()
								  ->findCirclesByUser($this->userId, $type, $name, $level);

		$data = [];
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
	 * @internal param $circleId
	 * @internal param string $iError
	 */
	public function detailsCircle($circleId) {

		try {
			$circle = $this->dbService->getCirclesMapper()
									  ->getDetailsFromCircle($this->userId, $circleId);

			if ($circle->getUser()
					   ->getLevel() >= Member::LEVEL_MEMBER
			) {
				$members = $this->dbService->getMembersMapper()
										   ->getMembersFromCircle(
											   $circleId, $circle->getUser()
										   );
				$circle->setMembers($members);
			}
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

			$circle = $this->dbService->getCirclesMapper()
									  ->getDetailsFromCircle(
										  $this->userId, $circleId
									  );

			try {
				$member = $this->dbService->getMembersMapper()
										  ->getMemberFromCircle(
											  $circle->getId(), $this->userId,
											  false
										  );

			} catch (MemberDoesNotExistException $m) {
				$member = new Member();
				$member->setCircleId($circle->getId());
				$member->setUserId($this->userId);
				$member->setLevel(Member::LEVEL_NONE);
				$member->setStatus(Member::STATUS_NONMEMBER);

				$this->dbService->getMembersMapper()
								->add($member);
			}


			$member->hasToBeAbleToJoinTheCircle();

			switch ($circle->getType()) {
				case Circle::CIRCLES_HIDDEN:
				case Circle::CIRCLES_PUBLIC:
					$member->joinOpenCircle();
					break;

				case Circle::CIRCLES_PRIVATE:
					$member->joinPrivateCircle();
					break;

				case Circle::CIRCLES_PERSONAL:
					throw new MemberCantJoinPersonalCircle();
					break;
			}

			$this->dbService->getMembersMapper()
							->editMember($member);

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
			$circle = $this->dbService->getCirclesMapper()
									  ->getDetailsFromCircle($this->userId, $circleId);

			$member = $this->dbService->getMembersMapper()
									  ->getMemberFromCircle($circle->getId(), $this->userId, false);

			$member->hasToBeMember();
			$member->cantBeOwner();

			$member->setStatus(Member::STATUS_NONMEMBER);
			$member->setLevel(Member::LEVEL_NONE);

			$this->dbService->getMembersMapper()
							->editMember($member);

		} catch (\Exception $e) {
			throw $e;
		}

		return $member;
	}


	/**
	 * destroy a circle.
	 *
	 * @param $circle
	 */
	public function removeCircle($circle) {

		$this->dbService->getMembersMapper()
						->removeAllFromCircle($circle);
		$this->dbService->getCirclesMapper()
						->destroy($circle);
	}


}