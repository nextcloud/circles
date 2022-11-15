<?php
/**
 * Circles - bring cloud-users closer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\RequestResultNotJsonException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC;
use OC\User\NoUserException;
use OCA\Circles\Circles\FileSharingBroadcaster;
use OCA\Circles\Db\AccountsRequest;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\FileSharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Exceptions\EmailAccountInvalidFormatException;
use OCA\Circles\Exceptions\GroupDoesNotExistException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberCantJoinCircleException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCP\IL10N;
use OCP\IUserManager;

/**
 * Class MembersService
 *
 * @deprecated
 * @package OCA\Circles\Service
 */
class MembersService {
	use TNCRequest;
	use TArrayTools;


	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var AccountsRequest */
	private $accountsRequest;

	/** @var FileSharesRequest */
	private $fileSharesRequest;

	/** @var TokensRequest */
	private $tokensRequest;

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
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param AccountsRequest $accountsRequest
	 * @param FileSharesRequest $fileSharesRequest
	 * @param TokensRequest $tokensRequest
	 * @param EventsService $eventsService
	 * @param GSUpstreamService $gsUpstreamService
	 * @param FileSharingBroadcaster $fileSharingBroadcaster
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IL10N $l10n, IUserManager $userManager, ConfigService $configService,
		DeprecatedCirclesRequest $circlesRequest, DeprecatedMembersRequest $membersRequest,
		AccountsRequest $accountsRequest,
		FileSharesRequest $fileSharesRequest, TokensRequest $tokensRequest, EventsService $eventsService,
		GSUpstreamService $gsUpstreamService, FileSharingBroadcaster $fileSharingBroadcaster,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->accountsRequest = $accountsRequest;
		$this->fileSharesRequest = $fileSharesRequest;
		$this->tokensRequest = $tokensRequest;
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

		return $this->filterDuplicate($curr, $new);
	}


	/**
	 * add a single member to a circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @param string $instance
	 * @param bool $force
	 *
	 * @return DeprecatedMember
	 * @throws EmailAccountInvalidFormatException
	 * @throws NoUserException
	 * @throws Exception
	 */
	private function addSingleMember(
		DeprecatedCircle $circle, $ident, $type, $instance = '', bool $force = false
	): DeprecatedMember {
		$this->verifyIdentBasedOnItsType($ident, $type, $instance);
		$this->verifyIdentContact($ident, $type);

		$member = $this->membersRequest->getFreshNewMember($circle->getUniqueId(), $ident, $type, $instance);
		$this->updateCachedName($member);

		$event = new GSEvent(GSEvent::MEMBER_ADD, false, $force);
		$event->setSeverity(GSEvent::SEVERITY_HIGH);
		$event->setAsync(true);
		$event->setDeprecatedCircle($circle);
		$event->setMember($member);
		$this->gsUpstreamService->newEvent($event);

		$new = $event->getMember();
		$new->setJoined($this->l10n->t('now'));
		if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED) {
//			$new->setLevel(Member::LEVEL_MEMBER);
			$new->setStatus(DeprecatedMember::STATUS_INVITED);
		} else {
//			$new->setLevel(Member::LEVEL_MEMBER);
			$new->setStatus(DeprecatedMember::STATUS_MEMBER);
		}

		if ($this->configService->isLocalInstance($new->getInstance())) {
			$new->setInstance('');
		}

		return $new;
	}


	/**
	 * add a bunch of users to a circle based on the type of the 'bunch'
	 *
	 * @param DeprecatedCircle $circle
	 * @param string $ident
	 * @param int $type
	 *
	 * @return DeprecatedMember[]
	 * @throws Exception
	 */
	private function addMassiveMembers(DeprecatedCircle $circle, $ident, $type): array {
		if ($type === DeprecatedMember::TYPE_GROUP) {
			return $this->addGroupMembers($circle, $ident);
		}

		if ($type === DeprecatedMember::TYPE_USER) {
			return $this->addMassiveMails($circle, $ident);
		}

		return [];
	}


	/**
	 * add a new member based on its type.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws CircleTypeNotValidException
	 * @throws MemberCantJoinCircleException
	 */
	public function addMemberBasedOnItsType(DeprecatedCircle $circle, DeprecatedMember $member) {
		$this->addLocalMember($circle, $member);
		$this->addEmailAddress($member);
		$this->addContact($member);
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws CircleTypeNotValidException
	 * @throws MemberCantJoinCircleException
	 */
	private function addLocalMember(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($member->getType() !== DeprecatedMember::TYPE_USER) {
			return;
		}

		$member->inviteToCircle($circle->getType());

		if ($circle->getType() === DeprecatedCircle::CIRCLES_CLOSED
			&& $this->configService->isInvitationSkipped()) {
			$member->joinCircle($circle->getType());
		}
	}


	/**
	 * add mail address as contact.
	 *
	 * @param DeprecatedMember $member
	 */
	private function addEmailAddress(DeprecatedMember $member) {
		if ($member->getType() !== DeprecatedMember::TYPE_MAIL) {
			return;
		}

		$member->addMemberToCircle();
	}


	/**
	 * // TODO - check this on GS setup
	 * Add contact as member.
	 *
	 * @param DeprecatedMember $member
	 */
	private function addContact(DeprecatedMember $member) {
		if ($member->getType() !== DeprecatedMember::TYPE_CONTACT) {
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
		if ($this->configService->isLocalInstance($instance)) {
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
		if ($type !== DeprecatedMember::TYPE_USER) {
			return;
		}

		if ($instance === '') {
			try {
				$ident = $this->miscService->getRealUserId($ident);
			} catch (NoUserException $e) {
				throw new NoUserException($this->l10n->t("This account does not exist"));
			}
		}
	}


	/**
	 * Verify if a mail have a valid format.
	 *
	 * @param string $ident
	 * @param int $type
	 *
	 * @throws EmailAccountInvalidFormatException
	 */
	private function verifyIdentEmailAddress(string $ident, int $type) {
		if ($type !== DeprecatedMember::TYPE_MAIL) {
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
	 * @throws EmailAccountInvalidFormatException
	 */
	private function verifyIdentContact(&$ident, $type) {
		if ($type !== DeprecatedMember::TYPE_CONTACT) {
			return;
		}

		$tmpContact = $this->userId . ':' . $ident;
		$result = MiscService::getContactData($tmpContact);
		if (empty($result)) {
			throw new NoUserException($this->l10n->t("This contact is not available"));
		}

		$ident = $tmpContact;
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param string $groupId
	 *
	 * @return DeprecatedMember[]
	 * @throws Exception
	 */
	private function addGroupMembers(DeprecatedCircle $circle, $groupId): array {
		$group = OC::$server->getGroupManager()
							->get($groupId);
		if ($group === null) {
			throw new GroupDoesNotExistException($this->l10n->t('This group does not exist'));
		}

		$members = [];
		foreach ($group->getUsers() as $user) {
			try {
				$members[] = $this->addSingleMember($circle, $user->getUID(), DeprecatedMember::TYPE_USER);
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
	 * @param DeprecatedCircle $circle
	 * @param string $mails
	 *
	 * @return DeprecatedMember[]
	 */
	private function addMassiveMails(DeprecatedCircle $circle, $mails): array {
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
				$members[] = $this->addMember($circle->getUniqueId(), $mail, DeprecatedMember::TYPE_MAIL, '');
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
	 * @return DeprecatedMember
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
	 * @return DeprecatedMember
	 * @throws MemberDoesNotExistException
	 */
	public function getMemberById(string $memberId): DeprecatedMember {
		return $this->membersRequest->forceGetMemberById($memberId);
	}


	/**
	 * @param DeprecatedMember $member
	 *
	 * @throws Exception
	 */
	public function updateMember(DeprecatedMember $member) {
		$event = new GSEvent(GSEvent::MEMBER_UPDATE);
		$event->setMember($member);
		$event->setDeprecatedCircle($this->getCircleFromMembership($member));

		$this->gsUpstreamService->newEvent($event);
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

		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			throw new CircleTypeNotValidException(
				$this->l10n->t('You cannot edit level in a personal circle')
			);
		}

		$member = $this->membersRequest->forceGetMember($circle->getUniqueId(), $name, $type, $instance);
		if ($member->getLevel() !== $level) {
			$event = new GSEvent(GSEvent::MEMBER_LEVEL, false, $force);
			$event->setDeprecatedCircle($circle);

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
	 * @return DeprecatedMember[]
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
		$event->setDeprecatedCircle($circle);
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

		$member = new DeprecatedMember($userId);
		$event->setMember($member);
		$event->getData()
			  ->s('userId', $userId);

		$this->gsUpstreamService->newEvent($event);
	}


	/**
	 * @param DeprecatedMember $member
	 * @param bool $fresh
	 */
	public function updateCachedName(DeprecatedMember $member, bool $fresh = true) {
		try {
			$cachedName = '';
			if ($member->getType() === DeprecatedMember::TYPE_USER) {
				$cachedName = $this->getUserDisplayName($member->getUserId(), $fresh);
			}

			if ($member->getType() === DeprecatedMember::TYPE_CONTACT) {
				$cachedName = $this->miscService->getContactDisplayName($member->getUserId());
			}

			if ($cachedName !== '') {
				$member->setCachedName($cachedName);
			}
		} catch (Exception $e) {
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 */
	public function updateCachedFromCircle(DeprecatedCircle $circle) {
		$members = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), DeprecatedMember::LEVEL_NONE, DeprecatedMember::TYPE_USER
		);

		foreach ($members as $member) {
			if (time() - $member->getCachedUpdate() > 72000) {
				$this->updateCachedName($member, false);
				$this->membersRequest->updateMemberInfo($member);
			}
		}
	}


	/**
	 * @param string $ident
	 * @param bool $fresh
	 *
	 * @return string
	 * @throws GSStatusException
	 */
	public function getUserDisplayName(string $ident, bool $fresh = false): string {
		if ($this->configService->isGSAvailable()) {
			return $this->getGlobalScaleUserDisplayName($ident);
		}

		if (!$fresh) {
			try {
				$account = $this->accountsRequest->getFromUserId($ident);

				return $this->get('displayName', $account);
			} catch (MemberDoesNotExistException $e) {
			}
		}

		$user = $this->userManager->get($ident);
		if ($user === null) {
			return '';
		}

		return $user->getDisplayName();
	}


	/**
	 * @param string $ident
	 *
	 * @return string
	 * @throws GSStatusException
	 */
	private function getGlobalScaleUserDisplayName(string $ident): string {
		$lookup = $this->configService->getGSLookup();

		$request = new NCRequest(ConfigService::GS_LOOKUP_USERS, Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($lookup);
		$request->addParam('search', $ident);
		$request->addParam('exact', '1');

		try {
			$users = $this->retrieveJson($request);

			return $this->get('name.value', $users);
		} catch (
			RequestNetworkException |
			RequestResultNotJsonException $e
		) {
		}

		return '';
	}


	/**
	 * @param DeprecatedMember $member
	 *
	 * @return DeprecatedCircle
	 * @throws CircleDoesNotExistException
	 */
	public function getCircleFromMembership(DeprecatedMember $member): DeprecatedCircle {
		return $this->circlesRequest->forceGetCircle($member->getCircleId());
	}


	/**
	 * @param DeprecatedMember[] $curr
	 * @param DeprecatedMember[] $new
	 *
	 * @return array
	 */
	private function filterDuplicate(array $curr, array $new): array {
		$base = [];
		foreach ($curr as $currMember) {
			$known = false;
			foreach ($new as $newMember) {
				if ($newMember->getMemberId() === $currMember->getMemberId()) {
					$known = true;
				}
			}
			if (!$known) {
				$base[] = $currMember;
			}
		}

		return array_merge($base, $new);
	}
}
