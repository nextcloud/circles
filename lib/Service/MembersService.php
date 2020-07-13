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
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\Member;
use OCP\IL10N;
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

	/** @var GSUpstreamService */
	private $gsUpstreamService;

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
		GSUpstreamService $gsUpstreamService, FileSharingBroadcaster $fileSharingBroadcaster,
		MiscService $miscService
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
		$this->gsUpstreamService = $gsUpstreamService;
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
	 * @param string $instance
	 *
	 * @param bool $force
	 *
	 * @return array
	 * @throws Exception
	 */
	public function addMember($circleUniqueId, $ident, $type, string $instance, bool $force = false) {
		if ($force === true) {
			$circle = $this->circlesRequest->forceGetCircle($circleUniqueId);
		} else {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();
		}

		$curr = $this->membersRequest->getMembers($circle->getUniqueId(), $circle->getHigherViewer(), $force);
		
		$new = $this->addMassiveMembers($circle, $ident, $type);
		if (empty($new)) {
			$new = [$this->addSingleMember($circle, $ident, $type, $instance, $force)];
		}

		return array_merge($curr, $new);
	}


	/**
	 * add a single member to a circle.
	 *
	 * @param Circle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @param string $instance
	 * @param bool $force
	 *
	 * @return Member
	 * @throws EmailAccountInvalidFormatException
	 * @throws NoUserException
	 * @throws Exception
	 */
	private function addSingleMember(Circle $circle, $ident, $type, $instance = '', bool $force = false
	): Member {
		$this->verifyIdentBasedOnItsType($ident, $type, $instance);
		$this->verifyIdentContact($ident, $type);

		$member = $this->membersRequest->getFreshNewMember($circle->getUniqueId(), $ident, $type, $instance);

		$event = new GSEvent(GSEvent::MEMBER_ADD, false, $force);
		$event->setSeverity(GSEvent::SEVERITY_HIGH);
		$event->setAsync(true);

		$event->setCircle($circle);
		$event->setMember($member);
		$this->gsUpstreamService->newEvent($event);

		$new = $event->getMember();
		$new->setJoined($this->l10n->t('now'));

		return $new;
	}


	/**
	 * add a bunch of users to a circle based on the type of the 'bunch'
	 *
	 * @param Circle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @return Member[]
	 * @throws Exception
	 */
	private function addMassiveMembers(Circle $circle, $ident, $type): array {
		if ($type === Member::TYPE_GROUP) {
			return $this->addGroupMembers($circle, $ident);
		}

		if ($type === Member::TYPE_USER) {
			return $this->addMassiveMails($circle, $ident);
		}

		return [];
	}


	/**
	 * add a new member based on its type.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws CircleTypeNotValidException
	 * @throws MemberCantJoinCircleException
	 */
	public function addMemberBasedOnItsType(Circle $circle, Member &$member) {
		$this->addLocalMember($circle, $member);
		$this->addEmailAddress($member);
		$this->addContact($member);
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws CircleTypeNotValidException
	 * @throws MemberCantJoinCircleException
	 */
	private function addLocalMember(Circle $circle, Member $member) {

		if ($member->getType() !== Member::TYPE_USER) {
			return;
		}

		$member->inviteToCircle($circle->getType());

		if ($circle->getType() === Circle::CIRCLES_CLOSED && $this->configService->isInvitationSkipped()) {
			$member->joinCircle($circle->getType());
		}
	}


	/**
	 * add mail address as contact.
	 *
	 * @param Member $member
	 */
	private function addEmailAddress(Member $member) {

		if ($member->getType() !== Member::TYPE_MAIL) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * // TODO - check this on GS setup
	 * Add contact as member.
	 *
	 * @param Member $member
	 */
	private function addContact(Member $member) {

		if ($member->getType() !== Member::TYPE_CONTACT) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * // TODO - check this on GS setup
	 * Verify the availability of an ident, based on its type.
	 *
	 * @param string $ident
	 * @param int $type
	 * @param string $instance
	 *
	 * @throws EmailAccountInvalidFormatException
	 * @throws NoUserException
	 */
	public function verifyIdentBasedOnItsType(&$ident, $type, string $instance = '') {
		if ($instance === $this->configService->getLocalCloudId()) {
			$instance = '';
		}

		$this->verifyIdentLocalMember($ident, $type, $instance);
		$this->verifyIdentEmailAddress($ident, $type);
//		$this->verifyIdentContact($ident, $type);
	}


	/**
	 * Verify if a local account is valid.
	 *
	 * @param $ident
	 * @param $type
	 *
	 * @param string $instance
	 *
	 * @throws NoUserException
	 */
	private function verifyIdentLocalMember(&$ident, $type, string $instance = '') {
		if ($type !== Member::TYPE_USER) {
			return;
		}

		if ($instance === '') {
			try {
				$ident = $this->miscService->getRealUserId($ident);
			} catch (NoUserException $e) {
				throw new NoUserException($this->l10n->t("This user does not exist"));
			}
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
	 * @return Member[]
	 * @throws Exception
	 */
	private function addGroupMembers(Circle $circle, $groupId): array {

		$group = OC::$server->getGroupManager()
							->get($groupId);
		if ($group === null) {
			throw new GroupDoesNotExistException($this->l10n->t('This group does not exist'));
		}

		$members = [];
		foreach ($group->getUsers() as $user) {
			try {
				$members[] = $this->addSingleMember($circle, $user->getUID(), Member::TYPE_USER);
			} catch (MemberAlreadyExistsException $e) {
			} catch (Exception $e) {
				throw $e;
			}
		}

		return $members;
	}


	/**
	 * // TODO - check this on GS setup
	 *
	 * @param Circle $circle
	 * @param string $mails
	 *
	 * @return Member[]
	 */
	private function addMassiveMails(Circle $circle, $mails): array {

		$mails = trim($mails);
		if (substr($mails, 0, 6) !== 'mails:') {
			return [];
		}

		$mails = substr($mails, 6);
		$members = [];
		foreach (explode(' ', $mails) as $mail) {
			if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				continue;
			}

			try {
				$members[] = $this->addMember($circle->getUniqueId(), $mail, Member::TYPE_MAIL, '');
			} catch (Exception $e) {
			}
		}

		return $members;
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
	 * @param string $instance
	 * @param int $level
	 * @param bool $force
	 *
	 * @return array
	 * @throws CircleDoesNotExistException
	 * @throws CircleTypeNotValidException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberDoesNotExistException
	 * @throws Exception
	 */
	public function levelMember(
		string $circleUniqueId, string $name, int $type, string $instance, int $level, bool $force = false
	) {
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

		$member = $this->membersRequest->forceGetMember($circle->getUniqueId(), $name, $type, $instance);
		if ($member->getLevel() !== $level) {
			$event = new GSEvent(GSEvent::MEMBER_LEVEL, false, $force);
			$event->setCircle($circle);

			$event->getData()
				  ->sInt('level', $level);
			$event->setMember($member);
			$this->gsUpstreamService->newEvent($event);
		}

		if ($force === false) {
			return $this->membersRequest->getMembers(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		} else {
			return $this->membersRequest->forceGetMembers($circle->getUniqueId());
		}

	}


	/**
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param int $type
	 * @param string $instance
	 * @param bool $force
	 *
	 * @return Member[]
	 * @throws CircleDoesNotExistException
	 * @throws ConfigNoCircleAvailableException
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws Exception
	 */
	public function removeMember(
		string $circleUniqueId, string $name, int $type, string $instance, bool $force = false
	): array {

		if ($force === false) {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$circle->getHigherViewer()
				   ->hasToBeModerator();
		} else {
			$circle = $this->circlesRequest->forceGetCircle($circleUniqueId);
		}

		$member = $this->membersRequest->forceGetMember($circleUniqueId, $name, $type, $instance);

		$event = new GSEvent(GSEvent::MEMBER_REMOVE, false, $force);
		$event->setCircle($circle);
		$event->setMember($member);
		$this->gsUpstreamService->newEvent($event);

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
	 * @param string $userId
	 *
	 * @throws Exception
	 */
	public function onUserRemoved(string $userId) {
		$event = new GSEvent(GSEvent::USER_DELETED, true, true);

		$member = new Member($userId);
		$event->setMember($member);
		$event->getData()
			  ->s('userId', $userId);

		$this->gsUpstreamService->newEvent($event);
	}


}

