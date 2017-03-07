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
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsBlockedException;
use OCA\Circles\Exceptions\MemberIsNotInvitedException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;
use OCP\IL10N;

class CirclesService {

	private $userId;
	private $l10n;
	private $configService;
	private $databaseService;
	private $miscService;

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
		$this->databaseService = $databaseService;
		$this->miscService = $miscService;
	}


	/**
	 * Create circle using this->userId as owner
	 *
	 * @param $type
	 * @param $name
	 *
	 * @return Circle
	 * @throws CircleAlreadyExistsException
	 * @throws CircleCreationException
	 * @throws CircleTypeDisabledException
	 * @throws ConfigNoCircleAvailable
	 */
	public function createCircle($type, $name) {

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
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
			$this->databaseService->getCirclesMapper()
								  ->create($circle, $owner);
			$this->databaseService->getMembersMapper()
								  ->add($owner);


		} catch (ConfigNoCircleAvailable $e) {
			throw $e;
		} catch (CircleAlreadyExistsException $e) {
			$this->databaseService->getCirclesMapper()
								  ->destroy($circle);
			throw $e;
		} catch (CircleCreationException $e) {
			$this->databaseService->getCirclesMapper()
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

		self::convertTypeStringToBitValue($type);

		if (!$this->configService->isCircleAllowed((int)$type)) {
			throw new CircleTypeDisabledException();
		}

		$result = $this->databaseService->getCirclesMapper()
										->findCirclesByUser($this->userId, $type, $name, $level);

		$data = [];
		foreach ($result as $item) {
			if ($name === '' || stripos($item->getName(), $name) !== false) {
				$data[] = $item;
			}
		}

		return $data;
	}


	/**
	 * returns details on circle and its members if this->userId is a member itself.
	 *
	 * @param $circleId
	 *
	 * @return Circle
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailable
	 * @internal param $circleId
	 * @internal param string $iError
	 */
	public function detailsCircle($circleId) {

		try {
			$circle = $this->databaseService->getCirclesMapper()
											->getDetailsFromCircle($this->userId, $circleId);

			if ($circle->getUser()
					   ->getLevel() >= Member::LEVEL_MEMBER
			) {
				$members = $this->databaseService->getMembersMapper()
												 ->getMembersFromCircle(
													 $circleId, ($circle->getUser()
																		->getLevel()
																 >= Member::LEVEL_MODERATOR)
												 );
				$circle->setMembers($members);
			}
		} catch (ConfigNoCircleAvailable $e) {
			throw $e;
		} catch (CircleDoesNotExistException $e) {
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
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailable
	 * @throws MemberAlreadyExistsException
	 * @throws MemberIsBlockedException
	 * @throws MemberIsNotInvitedException
	 */
	public function joinCircle($circleId) {

		try {

			$circle = $this->databaseService->getCirclesMapper()
											->getDetailsFromCircle(
												$this->userId, $circleId
											);

			try {
				$member = $this->databaseService->getMembersMapper()
												->getMemberFromCircle(
													$circle->getId(), $this->userId,
													($circle->getUser()
															->getLevel()
													 >= Member::LEVEL_MODERATOR)
												);

			} catch (MemberDoesNotExistException $m) {
				$member = new Member();
				$member->setCircleId($circle->getId());
				$member->setUserId($this->userId);
				$member->setLevel(Member::LEVEL_NONE);
				$member->setStatus(Member::STATUS_NONMEMBER);

				$this->databaseService->getMembersMapper()
									  ->add($member);
			}


			if ($member->getLevel() > 0) {
				throw new MemberAlreadyExistsException("You are already a member of this circle");
			}


			if ($member->getStatus() === Member::STATUS_BLOCKED) {
				throw new MemberIsBlockedException("You are blocked from this circle");
			}


			if ($member->getStatus() === Member::STATUS_NONMEMBER
				|| $member->getStatus() === Member::STATUS_KICKED
			) {
				if ($circle->getType() === Circle::CIRCLES_HIDDEN
					|| $circle->getType() === Circle::CIRCLES_PUBLIC
				) {
					$member->setStatus(Member::STATUS_MEMBER);
					$member->setLevel(Member::LEVEL_MEMBER);
				} else if ($circle->getType() === Circle::CIRCLES_PRIVATE) {
					$member->setStatus(Member::STATUS_REQUEST);
				} else {
					throw new MemberIsNotInvitedException("You are not invited into this circle");
				}
			}

			if ($member->getStatus() === Member::STATUS_INVITED) {
				$member->setStatus(Member::STATUS_MEMBER);
				$member->setLevel(Member::LEVEL_MEMBER);
			}

			$this->databaseService->getMembersMapper()
								  ->editMember($member);

		} catch (ConfigNoCircleAvailable $e) {
			throw $e;
		} catch (CircleDoesNotExistException $e) {
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
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailable
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsOwnerException
	 */
	public function leaveCircle($circleId) {

		try {
			$circle = $this->databaseService->getCirclesMapper()
											->getDetailsFromCircle(
												$this->userId, $circleId
											);

			$member = $this->databaseService->getMembersMapper()
											->getMemberFromCircle(
												$circle->getId(), $this->userId,
												($circle->getUser()
														->getLevel()
												 >= Member::LEVEL_MODERATOR)
											);

			if ($member === null || $member->getLevel() === 0) {
				throw new MemberDoesNotExistException("You are not member of this circle");
			}


			if ($member->getLevel() === Member::LEVEL_OWNER) {
				throw new MemberIsOwnerException("As the owner, you cannot leave this circle");
			}


			$member->setStatus(Member::STATUS_NONMEMBER);
			$member->setLevel(Member::LEVEL_NONE);

			$this->databaseService->getMembersMapper()
								  ->editMember(
									  $member
								  );

		} catch (ConfigNoCircleAvailable $e) {
			throw $e;
		} catch (CircleDoesNotExistException $e) {
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
		$this->databaseService->getMembersMapper()
							  ->removeAllFromCircle($circle);
		$this->databaseService->getCirclesMapper()
							  ->destroy($circle);
	}


	/**
	 * Convert a Type in String to its Bit Value
	 *
	 * @param $type
	 */
	public static function convertTypeStringToBitValue(&$type) {
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
	}

}