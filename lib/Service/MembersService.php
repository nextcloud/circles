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
use OC;
use OC\User\NoUserException;
use OCA\Circles\Circles\FileSharingBroadcaster;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\EmailAccountInvalidFormatException;
use OCA\Circles\Exceptions\GroupDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Exceptions\MemberTypeCantEditLevelException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IGroup;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;


/**
 * Class MembersService
 *
 * @package OCA\Circles\Service
 */
class MembersService {

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

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var CirclesService */
	private $circlesService;

	/** @var EventsService */
	private $eventsService;

	/** @var FileSharingBroadcaster */
	private $fileSharingBroadcaster;

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
	 * @param TokensRequest $tokensRequest
	 * @param CirclesService $circlesService
	 * @param EventsService $eventsService
	 * @param FileSharingBroadcaster $fileSharingBroadcaster
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IL10N $l10n, IUserManager $userManager, ConfigService $configService,
		CirclesRequest $circlesRequest, MembersRequest $membersRequest, SharesRequest $sharesRequest,
		TokensRequest $tokensRequest, CirclesService $circlesService, EventsService $eventsService,
		FileSharingBroadcaster $fileSharingBroadcaster, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->sharesRequest = $sharesRequest;
		$this->tokensRequest = $tokensRequest;
		$this->circlesService = $circlesService;
		$this->eventsService = $eventsService;
		$this->fileSharingBroadcaster = $fileSharingBroadcaster;
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
	 * @param bool $force
	 *
	 * @return array
	 * @throws Exception
	 */
	public function addMember($circleUniqueId, $ident, $type, bool $force = false) {

		if ($force === true) {
			$circle = $this->circlesRequest->forceGetCircle($circleUniqueId);
		} else {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();
		}

		if (!$this->addMassiveMembers($circle, $ident, $type)) {
			$this->addSingleMember($circle, $ident, $type);
		}

		return $this->membersRequest->getMembers($circle->getUniqueId(), $circle->getHigherViewer(), $force);
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
		$this->verifyIdentWithGroupBackend($circle, $ident, $type);

		$member = $this->membersRequest->getFreshNewMember($circle->getUniqueId(), $ident, $type);
		$member->hasToBeInviteAble();

		$this->circlesService->checkThatCircleIsNotFull($circle);

		$this->addMemberBasedOnItsType($circle, $member);

		$this->membersRequest->updateMember($member);
		$this->fileSharingBroadcaster->sendMailAboutExistingShares($circle, $member);

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

		if ($type === Member::TYPE_USER) {
			return $this->addMassiveMails($circle, $ident);
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
	 * @throws Exception
	 */
	private function addLocalMember(Circle $circle, Member $member) {

		if ($member->getType() !== Member::TYPE_USER) {
			return;
		}

		$member->inviteToCircle($circle->getType());

		if ($this->configService->isInvitationSkipped()) {
			$member->joinCircle($circle->getType());
		}
	}


	/**
	 * add mail address as contact.
	 *
	 * @param Member $member
	 *
	 * @throws Exception
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
	 * @throws Exception
	 */
	private function addContact(Member $member) {

		if ($member->getType() !== Member::TYPE_CONTACT) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * Verify the availability of an ident when Group Backend is enabled
	 *
	 * @param Circle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @throws Exception
	 */
	private function verifyIdentWithGroupBackend(Circle $circle, $ident, $type) {
		if ($this->configService->isGroupsBackend() &&
			in_array($type, [Member::TYPE_MAIL, Member::TYPE_CONTACT], true) &&
			in_array($circle->getType(), [Circle::CIRCLES_CLOSED, Circle::CIRCLES_PUBLIC], true)
		) {
			if ($type === Member::TYPE_MAIL) {
				$errorMessage = 'You cannot add a mail address as member of your Circle';
			}
			if ($type === Member::TYPE_CONTACT) {
				$errorMessage = 'You cannot add a contact as member of your Circle';
			}
			throw new EmailAccountInvalidFormatException(
				$this->l10n->t($errorMessage)
			);
		}
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

		if ($this->configService->isAccountOnly()) {
			throw new EmailAccountInvalidFormatException(
				$this->l10n->t('You cannot add a mail address as member of your Circle')
			);
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
	 * @throws EmailAccountInvalidFormatException
	 */
	private function verifyIdentContact(&$ident, $type) {
		if ($type !== Member::TYPE_CONTACT) {
			return;
		}

		if ($this->configService->isAccountOnly()) {
			throw new EmailAccountInvalidFormatException(
				$this->l10n->t('You cannot add a contact as member of your Circle')
			);
		}

		$tmpContact = $this->userId . ':' . $ident;
		$result = MiscService::getContactData($tmpContact);
		if (empty($result)) {
			throw new NoUserException($this->l10n->t("This contact is not available"));
		}

		$ident = $tmpContact;
	}


	/**
	 * @param Circle $circle
	 * @param string $groupId
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function addGroupMembers(Circle $circle, $groupId) {

		$groupManager = OC::$server->getGroupManager();
		$group = $groupManager->get($groupId);

		$user = OC::$server->getUserSession()->getUser();

		if (!$this->configService->isAddingAnyGroupMembersAllowed() &&
			$group instanceof IGroup && $user instanceof IUser &&
			!$group->inGroup($user) && !$groupManager->isAdmin($user->getUID())
		) {
			$group = null;
		}

		if ($group === null) {
			throw new GroupDoesNotExistException($this->l10n->t('This group does not exist'));
		}

		foreach ($group->getUsers() as $user) {
			try {
				$this->addSingleMember($circle, $user->getUID(), Member::TYPE_USER);
			} catch (MemberAlreadyExistsException $e) {
			} catch (Exception $e) {
				throw $e;
			}
		}

		return true;
	}


	/**
	 * @param Circle $circle
	 * @param string $mails
	 *
	 * @return bool
	 */
	private function addMassiveMails(Circle $circle, $mails) {

		$mails = trim($mails);
		if (substr($mails, 0, 6) !== 'mails:') {
			return false;
		}

		$mails = substr($mails, 6);
		foreach (explode(' ', $mails) as $mail) {
			if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				continue;
			}

			try {
				$this->addMember($circle->getUniqueId(), $mail, Member::TYPE_MAIL);
			} catch (Exception $e) {
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
	 * @param bool $forceAll
	 *
	 * @return Member
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberDoesNotExistException
	 */
	public function getMember($circleId, $userId, $type, $forceAll = false) {
		if (!$forceAll) {
			$this->circlesRequest->getCircle($circleId, $this->userId)
								 ->getHigherViewer()
								 ->hasToBeMember();
		}

		$member = $this->membersRequest->forceGetMember($circleId, $userId, $type);
		$member->setNote('');

		return $member;
	}


	/**
	 * @param string $memberId
	 *
	 * @return Member
	 * @throws MemberDoesNotExistException
	 */
	public function getMemberById(string $memberId): Member {
		return $this->membersRequest->forceGetMemberById($memberId);
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param int $type
	 * @param int $level
	 * @param bool $force
	 *
	 * @return array
	 * @throws CircleDoesNotExistException
	 * @throws CircleTypeNotValidException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberDoesNotExistException
	 * @throws MemberTypeCantEditLevelException
	 * @throws Exception
	 */
	public function levelMember($circleUniqueId, $name, $type, $level, bool $force = false) {

		$level = (int)$level;
		if ($force === false) {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
		} else {
			$circle = $this->circlesRequest->forceGetCircle($circleUniqueId);
		}

		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			throw new CircleTypeNotValidException(
				$this->l10n->t('You cannot edit level in a personal circle')
			);
		}

		$member = $this->membersRequest->forceGetMember($circle->getUniqueId(), $name, $type);
		$member->levelHasToBeEditable();
		$this->updateMemberLevel($circle, $member, $level, $force);

		if ($force === false) {
			return $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		} else {
			return $this->membersRequest->forceGetMembers($circle->getUniqueId());
		}

	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param $level
	 * @param bool $force
	 *
	 * @throws Exception
	 */
	private function updateMemberLevel(Circle $circle, Member $member, $level, bool $force = false) {
		if ($member->getLevel() === $level) {
			return;
		}

		if ($level === Member::LEVEL_OWNER) {
			$this->switchOwner($circle, $member, $force);
		} else {
			$this->editMemberLevel($circle, $member, $level, $force);
		}

		$this->eventsService->onMemberLevel($circle, $member);
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param $level
	 * @param bool $force
	 *
	 * @throws Exception
	 */
	private function editMemberLevel(Circle $circle, Member &$member, $level, bool $force = false) {
		if ($force === false) {
			$isMod = $circle->getHigherViewer();
			$isMod->hasToBeModerator();
			$isMod->hasToBeHigherLevel($level);

			$member->hasToBeMember();
			$isMod->hasToBeHigherLevel($member->getLevel());
		}

		$member->cantBeOwner();

		$member->setLevel($level);
		$this->membersRequest->updateMember($member);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param bool $force
	 *
	 * @throws Exception
	 */
	private function switchOwner(Circle $circle, Member &$member, bool $force = false) {
		if ($force === false) {
			$isMod = $circle->getHigherViewer();

			// should already be possible from an NCAdmin, but not enabled in the frontend.
			$this->circlesService->hasToBeOwner($isMod);
		} else {
			$isMod = $circle->getOwner();
		}

		$member->hasToBeMember();
		$member->cantBeOwner();

		$member->setLevel(Member::LEVEL_OWNER);
		$this->membersRequest->updateMember($member);

		$isMod->setLevel(Member::LEVEL_ADMIN);
		$this->membersRequest->updateMember($isMod);
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param $type
	 * @param bool $force
	 *
	 * @return array
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws MemberIsOwnerException
	 * @throws ModeratorIsNotHighEnoughException
	 */
	public function removeMember($circleUniqueId, $name, $type, bool $force = false) {

		if ($force === false) {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();
		} else {
			$circle = $this->circlesRequest->forceGetCircle($circleUniqueId);
		}

		$member = $this->membersRequest->forceGetMember($circleUniqueId, $name, $type);
		$member->hasToBeMemberOrAlmost();
		$member->cantBeOwner();

		if ($force === false) {
			$circle->getHigherViewer()
				   ->hasToBeHigherLevel($member->getLevel());
		}

		$this->eventsService->onMemberLeaving($circle, $member);

		$this->membersRequest->removeMember($member);
		$this->sharesRequest->removeSharesFromMember($member);
		$this->tokensRequest->removeTokensFromMember($member);

		if ($force === false) {
			return $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		} else {
			return $this->membersRequest->forceGetMembers($circle->getUniqueId());
		}
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
