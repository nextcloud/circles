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


use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
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

	/** @var CirclesMapper */
	private $dbCircles;

	/** @var MembersMapper */
	private $dbMembers;

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
		$this->miscService = $miscService;

		$this->dbCircles = $databaseService->getCirclesMapper();
		$this->dbMembers = $databaseService->getMembersMapper();
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
		$owner = new Member($this->l10n, $this->userId);

		try {
			$this->dbCircles->create($circle);
			$owner->setLevel(Member::LEVEL_OWNER)
				  ->setStatus(Member::STATUS_MEMBER)
				  ->setCircleId($circle->getId());
			$this->dbMembers->add($circle->getOwner());
		} catch (\Exception $e) {
			$this->dbCircles->destroy($circle->getId());
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
		$result = $this->dbCircles->findCirclesByUser($this->userId, $type, $name, $level);
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
			$circle = $this->dbCircles->getDetailsFromCircle($circleId, $this->userId);
			if ($circle->getUser()
					   ->isLevel(Member::LEVEL_MEMBER)
			) {
				$members = $this->dbMembers->getMembersFromCircle(
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
			$circle = $this->dbCircles->getDetailsFromCircle($circleId, $this->userId);

			try {
				$member = $this->dbMembers->getMemberFromCircle($circle->getId(), $this->userId);
			} catch (MemberDoesNotExistException $m) {
				$member = new Member($this->l10n, $this->userId, $circle->getId());
				$this->dbMembers->add($member);
			}

			$member->hasToBeAbleToJoinTheCircle();
			$member->joinCircle($circle->getType());
			$this->dbMembers->editMember($member);

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
			$circle = $this->dbCircles->getDetailsFromCircle($circleId, $this->userId);
			$member = $this->dbMembers->getMemberFromCircle($circle->getId(), $this->userId, false);

			if (!$member->isAlmostMember()) {
				$member->hasToBeMember();
			}

			$member->cantBeOwner();
			$member->setStatus(Member::STATUS_NONMEMBER);
			$member->setLevel(Member::LEVEL_NONE);
			$this->dbMembers->editMember($member);

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
			$member = $this->dbMembers->getMemberFromCircle($circleId, $this->userId, false);
			$member->hasToBeOwner();

			$this->dbMembers->removeAllFromCircle($circleId);
			$this->dbCircles->destroy($circleId);

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
		return $this->dbCircles->getDetailsFromCircleByName($circleName);
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
	 *
	 * @return string
	 */
	public static function getCircleIcon($type) {
		$urlGen = \OC::$server->getURLGenerator();
		switch ($type) {
			case Circle::CIRCLES_PERSONAL:
				return $urlGen->imagePath('circles', 'personal.svg');
			case Circle::CIRCLES_PRIVATE:
				return $urlGen->imagePath('circles', 'private.svg');
			case Circle::CIRCLES_HIDDEN:
				return $urlGen->imagePath('circles', 'hidden.svg');
			case Circle::CIRCLES_PUBLIC:
				return $urlGen->imagePath('circles', 'public.svg');
		}

		return $urlGen->imagePath('circles', 'black_circle.svg');
	}

}