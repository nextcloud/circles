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


use Exception;
use OC\User\NoUserException;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\EmailAccountInvalidFormatException;
use OCA\Circles\Exceptions\GroupDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Util;

class MembersService extends BaseService {

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var SharesRequest */
	private $sharesRequest;

	/** @var CirclesService */
	private $circlesService;

	/** @var EventsService */
	private $eventsService;

	/** @var MiscService */
	private $miscService;

	/**
	 * MembersService constructor.
	 *
	 * @param string $userId
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param ConfigService $configService
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param SharesRequest $sharesRequest
	 * @param CirclesService $circlesService
	 * @param EventsService $eventsService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IL10N $l10n, IUserManager $userManager, ConfigService $configService,
		CirclesRequest $circlesRequest, MembersRequest $membersRequest,
		SharesRequest $sharesRequest, CirclesService $circlesService, EventsService $eventsService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->sharesRequest = $sharesRequest;
		$this->circlesService = $circlesService;
		$this->eventsService = $eventsService;
		$this->miscService = $miscService;
	}


	/**
	 * addMember();
	 *
	 * add a new member to a circle.
	 *
	 * @param string $circleUniqueId
	 * @param $ident
	 * @param int $type
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function addMember($circleUniqueId, $ident, $type) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();
			
			if (!$this->addMassiveMembers($circle, $ident, $type)) {
				$this->addSingleMember($circle, $ident, $type);
			}

			$action = ($type == Circle::CIRCLES_CLOSED ? 'invited' : 'added');
			$circleName = $circle->getName();
			$user = $this->getUser()->getDisplayName();
			$this->miscService->log("user $user $action member $ident to circle $circleName");
		} catch (\Exception $e) {
			throw $e;
		}

		return $this->membersRequest->getMembers(
			$circle->getUniqueId(), $circle->getHigherViewer()
		);
	}


	/**
	 * add a single member to a circle.
	 *
	 * @param Circle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @throws MemberAlreadyExistsException
	 * @throws Exception
	 */
	private function addSingleMember(Circle $circle, $ident, $type) {
		$this->verifyIdentBasedOnItsType($ident, $type);

		$member = $this->membersRequest->getFreshNewMember($circle->getUniqueId(), $ident, $type);
		$member->hasToBeInviteAble();

		$this->circlesService->checkThatCircleIsNotFull($circle);

		$this->addMemberBasedOnItsType($circle, $member);

		$this->membersRequest->updateMember($member);
		$this->eventsService->onMemberNew($circle, $member);
	}


	/**
	 * add a bunch of users to a circle based on the type of the 'bunch'
	 *
	 * @param Circle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function addMassiveMembers(Circle $circle, $ident, $type) {

		if ($type === Member::TYPE_GROUP) {
			return $this->addGroupMembers($circle, $ident);
		}

		return false;
	}


	/**
	 * add a new member based on its type.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws Exception
	 */
	private function addMemberBasedOnItsType(Circle $circle, Member &$member) {
		$this->addLocalMember($circle, $member);
		$this->addEmailAddress($member);
		$this->addContact($member);
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws \Exception
	 */
	private function addLocalMember(Circle $circle, Member $member) {

		if ($member->getType() !== Member::TYPE_USER) {
			return;
		}

		$member->inviteToCircle($circle->getType());
	}


	/**
	 * add mail address as contact.
	 *
	 * @param Member $member
	 *
	 * @throws \Exception
	 */
	private function addEmailAddress(Member $member) {

		if ($member->getType() !== Member::TYPE_MAIL) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * Add contact as member.
	 *
	 * @param Member $member
	 *
	 * @throws \Exception
	 */
	private function addContact(Member $member) {

		if ($member->getType() !== Member::TYPE_CONTACT) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * Verify the availability of an ident, based on its type.
	 *
	 * @param string $ident
	 * @param int $type
	 *
	 * @throws Exception
	 */
	private function verifyIdentBasedOnItsType(&$ident, $type) {
		$this->verifyIdentLocalMember($ident, $type);
		$this->verifyIdentEmailAddress($ident, $type);
		$this->verifyIdentContact($ident, $type);
	}


	/**
	 * Verify if a local account is valid.
	 *
	 * @param $ident
	 * @param $type
	 *
	 * @throws NoUserException
	 */
	private function verifyIdentLocalMember(&$ident, $type) {
		if ($type !== Member::TYPE_USER) {
			return;
		}

		try {
			$ident = $this->miscService->getRealUserId($ident);
		} catch (NoUserException $e) {
			throw new NoUserException($this->l10n->t("This user does not exist"));
		}
	}


	/**
	 * Verify if a mail have a valid format.
	 *
	 * @param $ident
	 * @param $type
	 *
	 * @throws EmailAccountInvalidFormatException
	 */
	private function verifyIdentEmailAddress(&$ident, $type) {
		if ($type !== Member::TYPE_MAIL) {
			return;
		}

		if (!filter_var($ident, FILTER_VALIDATE_EMAIL)) {
			throw new EmailAccountInvalidFormatException(
				$this->l10n->t('Email format is not valid')
			);
		}
	}


	/**
	 * Verify if a contact exist in current user address books.
	 *
	 * @param $ident
	 * @param $type
	 *
	 * @throws NoUserException
	 */
	private function verifyIdentContact(&$ident, $type) {
		if ($type !== Member::TYPE_CONTACT) {
			return;
		}

		$tmpContact = $this->userId . ':' . $ident;
		try {
			MiscService::getContactData($tmpContact);
		} catch (Exception $e) {
			throw new NoUserException($this->l10n->t("This contact is not available"));
		}

		$ident = $tmpContact;
	}


	/**
	 * @param Circle $circle
	 * @param string $groupId
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function addGroupMembers(Circle $circle, $groupId) {

		$group = \OC::$server->getGroupManager()
							 ->get($groupId);
		if ($group === null) {
			throw new GroupDoesNotExistException($this->l10n->t('This group does not exist'));
		}

		foreach ($group->getUsers() as $user) {
			try {
				$this->addSingleMember($circle, $user->getUID(), Member::TYPE_USER);
			} catch (MemberAlreadyExistsException $e) {
			} catch (\Exception $e) {
				throw $e;
			}
		}

		return true;
	}


	/**
	 * getMember();
	 *
	 * Will return any data of a user related to a circle (as a Member). User can be a 'non-member'
	 * Viewer needs to be at least Member of the Circle
	 *
	 * @param $circleId
	 * @param $userId
	 * @param $type
	 *
	 * @return Member
	 * @throws \Exception
	 */
	public function getMember($circleId, $userId, $type) {

		try {
			$this->circlesRequest->getCircle($circleId, $this->userId)
								 ->getHigherViewer()
								 ->hasToBeMember();

			$member = $this->membersRequest->forceGetMember($circleId, $userId, $type);
			$member->setNote('');

			return $member;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param int $type
	 * @param int $level
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function levelMember($circleUniqueId, $name, $type, $level) {

		$level = (int)$level;
		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
				throw new CircleTypeNotValidException(
					$this->l10n->t('You cannot edit level in a personal circle')
				);
			}

			$member = $this->membersRequest->forceGetMember($circle->getUniqueId(), $name, $type);
			$member->levelHasToBeEditable();
			$this->updateMemberLevel($circle, $member, $level);
			
			$circleName = $circle->getName();
			$levelString = Member::getLevelStringFromCode($level);
			$memberName = $member->getDisplayName();
			$user = $this->getUser()->getDisplayName();
			$this->miscService->log("$user changed level of $memberName from circle $circleName to $levelString");
			return $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		} catch (\Exception $e) {
			throw $e;
		}

	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param $level
	 *
	 * @throws Exception
	 */
	private function updateMemberLevel(Circle $circle, Member $member, $level) {
		if ($member->getLevel() === $level) {
			return;
		}

		if ($level === Member::LEVEL_OWNER) {
			$this->switchOwner($circle, $member);
		} else {
			$this->editMemberLevel($circle, $member, $level);
		}

		$this->eventsService->onMemberLevel($circle, $member);
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param $level
	 *
	 * @throws \Exception
	 */
	private function editMemberLevel(Circle $circle, Member &$member, $level) {
		try {
			$isMod = $circle->getHigherViewer();
			$isMod->hasToBeModerator();
			$isMod->hasToBeHigherLevel($level);

			$member->hasToBeMember();
			$member->cantBeOwner();
			$isMod->hasToBeHigherLevel($member->getLevel());

			$member->setLevel($level);
			$this->membersRequest->updateMember($member);
		} catch (\Exception $e) {
			throw $e;
		}

	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws \Exception
	 */
	private function switchOwner(Circle $circle, Member &$member) {
		try {
			$isMod = $circle->getHigherViewer();

			// should already be possible from an NCAdmin, but not enabled in the frontend.
			$this->circlesService->hasToBeOwner($isMod);

			$member->hasToBeMember();
			$member->cantBeOwner();

			$member->setLevel(Member::LEVEL_OWNER);
			$this->membersRequest->updateMember($member);

			$isMod->setLevel(Member::LEVEL_ADMIN);
			$this->membersRequest->updateMember($isMod);

		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param $type
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function removeMember($circleUniqueId, $name, $type) {

		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();

			$member = $this->membersRequest->forceGetMember($circleUniqueId, $name, $type);
			$member->hasToBeMemberOrAlmost();
			$member->cantBeOwner();

			$circle->getHigherViewer()
				   ->hasToBeHigherLevel($member->getLevel());

			$user = $this->getUser()->getDisplayName();
			$memberName = $member->getDisplayName();
			$circleName = $circle->getName();
			$this->miscService->log("user $user removed member $memberName from circle $circleName");
		} catch (\Exception $e) {
			throw $e;
		}

		$this->eventsService->onMemberLeaving($circle, $member);

		$this->membersRequest->removeMember($member);
		$this->sharesRequest->removeSharesFromMember($member);

		return $this->membersRequest->getMembers(
			$circle->getUniqueId(), $circle->getHigherViewer()
		);
	}


	/**
	 * When a user is removed, remove him from all Circles
	 *
	 * @param $userId
	 */
	public function onUserRemoved($userId) {
		$this->membersRequest->removeAllMembershipsFromUser($userId);
	}


}