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


use \OCA\Circles\Model\Circle;
use \OCA\Circles\Model\iError;
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

	public function addMember($circleid, $name, &$iError = '') {

		if ($iError === '' || $iError === null) {
			$iError = new iError();
		}

		if (!$this->userManager->userExists($name)) {
			$iError->setCode(iError::MEMBER_DOES_NOT_EXIST)
				   ->setMessage("The selected user does not exist");

			return null;
		}

		$ismod = $this->databaseService->getMembersMapper()
									   ->getMemberFromCircle($circleid, $this->userId, $iError);

		if ($ismod === null) {
			return null;
		}

		if ($ismod->getLevel() < Member::LEVEL_MODERATOR) {
			$iError->setCode(iError::MEMBER_NEEDS_MODERATOR_RIGHTS)
				   ->setMessage("You have not enough rights");

			return null;
		}

		if ($this->databaseService->getMembersMapper()
								  ->getMemberFromCircle($circleid, $name) !== null
		) {
			$iError->setCode(iError::MEMBER_ALREADY_IN_CIRCLE)
				   ->setMessage("This user is already in the circle");

			return null;
		}


		$circle = $this->databaseService->getCirclesMapper()
										->getDetailsFromCircle($this->userId, $circleid, $iError);

		$member = new Member();
		$member->setCircleId($circleid);
		$member->setUserId($name);

		switch ($circle->getType()) {
			case Circle::CIRCLES_PERSONAL:
			case Circle::CIRCLES_HIDDEN:
			case Circle::CIRCLES_PUBLIC:
				$member->setLevel(Member::LEVEL_MEMBER);
				$member->setStatus(Member::STATUS_MEMBER);
				break;

			case Circle::CIRCLES_PRIVATE:
				$member->setLevel(Member::LEVEL_NONE);
				$member->setStatus(Member::STATUS_INVITED);
				break;
		}

		if (!$this->databaseService->getMembersMapper()
								   ->add($member, $iError)
		) {
			return null;
		}

		return $this->databaseService->getMembersMapper()
									 ->getMembersFromCircle(
										 $circleid, ($circle->getUser()
															->getLevel()
													 >= Member::LEVEL_MODERATOR),
										 $iError
									 );
	}

	public function removeMember($circleid, $name, &$iError = '') {

		if ($iError === '' || $iError === null) {
			$iError = new iError();
		}

		$ismod = $this->databaseService->getMembersMapper()
									   ->getMemberFromCircle($circleid, $this->userId, $iError);

		if ($ismod === null) {
			return null;
		}

		if ($ismod->getLevel() < Member::LEVEL_MODERATOR) {
			$iError->setCode(iError::MEMBER_NEEDS_MODERATOR_RIGHTS)
				   ->setMessage("You have not enough rights");

			return null;
		}

		$curr = $this->databaseService->getMembersMapper()
									  ->getMemberFromCircle($circleid, $name);
		if ($curr === null) {
			$iError->setCode(iError::MEMBER_NOT_IN_CIRCLE)
				   ->setMessage("This user is not a member of this circle");

			return null;
		}

		if ($curr->getLevel() === Member::LEVEL_OWNER) {
			$iError->setCode(iError::MEMBER_CANT_REMOVE_OWNER)
				   ->setMessage("This user is the owner of the circle");

			return null;

		}

		$member = new Member();
		$member->setCircleId($circleid);
		$member->setUserId($name);

		if (!$this->databaseService->getMembersMapper()
								   ->remove($member, $iError)
		) {
			return null;
		}

		$circle = $this->databaseService->getCirclesMapper()
										->getDetailsFromCircle($this->userId, $circleid, $iError);

		return $this->databaseService->getMembersMapper()
									 ->getMembersFromCircle(
										 $circleid, ($circle->getUser()
															->getLevel()
													 >= Member::LEVEL_MODERATOR),
										 $iError
									 );
	}

}