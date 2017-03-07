<?php
/**
 * Circles - bring cloud-users closer
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
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;
use OCP\IL10N;
use OCP\IUserManager;

class MembersService {

	private $userId;
	private $l10n;
	private $configService;
	private $databaseService;
	private $miscService;

	public function __construct(
		$userId,
		IL10N $l10n,
		IUserManager $userManager,
		ConfigService $configService,
		DatabaseService $databaseService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->databaseService = $databaseService;
		$this->miscService = $miscService;
	}


//	public function searchMembers($name) {
//		$iError = new iError();
//
//		$result = $this->userManager->get($name);
//		$this->miscService->log("___" . var_export($result, true));
////		if ($user != null) {
////
////			$realname = $user->getDisplayName();
//
//		$result = [
//			'name'   => $name,
//			'result' => $result,
//			'status' => 1,
//			'error'  => $iError->toArray()
//		];
//
//		return $result;
//	}

	/**
	 * @param $circleId
	 * @param $name
	 *
	 * @return array
	 * @throws CircleDoesNotExistException
	 * @throws MemberAlreadyExistsException
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws NoUserException
	 */
	public function addMember($circleId, $name) {

		if (!$this->userManager->userExists($name)) {
			throw new NoUserException("The selected user does not exist");
		}

		// we check that this->userId is moderator
		try {
			$this->databaseService->getMembersMapper()
								  ->getMemberFromCircle($circleId, $this->userId)
								  ->isModerator();
		} catch (MemberDoesNotExistException $e) {
			throw $e;
		} catch (MemberIsNotModeratorException $e) {
			throw new MemberIsNotModeratorException("You are not moderator of this circle");
		}

		try {
			$member = $this->databaseService->getMembersMapper()
											->getMemberFromCircle($circleId, $name);

		} catch (MemberDoesNotExistException $e) {
			$member = new Member();
			$member->setCircleId($circleId);
			$member->setUserId($name);
			$member->setLevel(Member::LEVEL_NONE);
			$member->setStatus(Member::STATUS_NONMEMBER);

			$this->databaseService->getMembersMapper()
								  ->add(
									  $member
								  );
		}

		try {
			$circle = $this->databaseService->getCirclesMapper()
											->getDetailsFromCircle($this->userId, $circleId);
		} catch (CircleDoesNotExistException $e) {
			throw $e;
		}

		if ($member->getLevel() > Member::LEVEL_NONE
			|| ($member->getStatus() !== Member::STATUS_NONMEMBER
				&& $member->getStatus() !== Member::STATUS_REQUEST)
		) {
			throw new MemberAlreadyExistsException();
		}

		$member->setCircleId($circleId);
		$member->setUserId($name);

		if ($circle->getType() === Circle::CIRCLES_PRIVATE) {
			$this->inviteMemberToPrivateCircle($member);
		} else {
			$this->addMemberToCircle($member);
		}

		$this->databaseService->getMembersMapper()
							  ->editMember($member);

		return $this->databaseService->getMembersMapper()
									 ->getMembersFromCircle(
										 $circleId, ($circle->getUser()
															->getLevel()
													 >= Member::LEVEL_MODERATOR)
									 );
	}


	/**
	 * Invite a Member to a private Circle, or accept his request.
	 *
	 * @param $member
	 */
	private function inviteMemberToPrivateCircle(&$member) {
		if ($member->getStatus() === Member::STATUS_REQUEST) {
			self::AddMemberToCircle($member);
		} else {
			$member->setLevel(Member::LEVEL_NONE);
			$member->setStatus(Member::STATUS_INVITED);
		}
	}

	/**
	 * add a member to a circle.
	 *
	 * @param $member
	 */
	private function addMemberToCircle(&$member) {
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);
	}


	/**
	 * @param $circleId
	 * @param $name
	 *
	 * @return array
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws MemberIsOwnerException
	 */
	public function removeMember($circleId, $name) {

		try {
			$ismod = $this->databaseService->getMembersMapper()
										   ->getMemberFromCircle($circleId, $this->userId);
		} catch (MemberDoesNotExistException $e) {
			throw $e;
		}


		if ($ismod->getLevel() < Member::LEVEL_MODERATOR) {
			throw new MemberIsNotModeratorException("You are not moderator of this circle");
		}

		try {
			$member = $this->databaseService->getMembersMapper()
											->getMemberFromCircle($circleId, $name);
		} catch (MemberDoesNotExistException $e) {
			throw $e;
		}


		if ($member->getLevel() === Member::LEVEL_OWNER) {
			throw new MemberIsOwnerException();
		}

		$this->databaseService->getMembersMapper()
							  ->remove($member);

		$circle = $this->databaseService->getCirclesMapper()
										->getDetailsFromCircle(
											$this->userId, $circleId
										);

		return $this->databaseService->getMembersMapper()
									 ->getMembersFromCircle(
										 $circleId, ($circle->getUser()
															->getLevel()
													 >= Member::LEVEL_MODERATOR)
									 );
	}

}