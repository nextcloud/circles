<?php


/**
 * Circles - Bring cloud-users closer together.
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


use Exception;
use OCA\Circles\Circles\FileSharingBroadcaster;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\NotLocalMemberException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\DavCard;
use OCA\Circles\Model\Member;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\App\ManagerEvent;
use OCP\Federation\ICloudIdManager;
use OCP\IUserManager;
use OCP\EventDispatcher\GenericEvent;


/**
 * Class DavService
 *
 * @package OCA\Circles\Service
 */
class DavService {


	/** @var string */
	private $userId;

	/** @var IUserManager */
	private $userManager;

	/** @var CardDavBackend */
	private $cardDavBackend;

	/** @var ICloudIdManager */
	private $cloudManager;

	/** @var FileSharingBroadcaster */
	private $fileSharingBroadcaster;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/** @var array */
	private $migratedBooks = [];


	/**
	 * TimezoneService constructor.
	 *
	 * @param string $userId
	 * @param IUserManager $userManager
	 * @param CardDavBackend $cardDavBackend
	 * @param ICloudIdManager $cloudManager
	 * @param CirclesRequest $circlesRequest
	 * @param FileSharingBroadcaster $fileSharingBroadcaster
	 * @param MembersRequest $membersRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IUserManager $userManager, CardDavBackend $cardDavBackend, ICloudIdManager $cloudManager,
		FileSharingBroadcaster $fileSharingBroadcaster, CirclesRequest $circlesRequest,
		MembersRequest $membersRequest, ConfigService $configService, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->cardDavBackend = $cardDavBackend;
		$this->cloudManager = $cloudManager;
		$this->fileSharingBroadcaster = $fileSharingBroadcaster;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param ManagerEvent $event
	 */
	public function onAppEnabled(ManagerEvent $event) {
		if ($event->getAppID() !== 'circles') {
			return;
		}

		try {
			$this->migration();
		} catch (Exception $e) {
		}
	}


	/**
	 * @param GenericEvent $event
	 */
	public function onCreateCard(GenericEvent $event) {
		$davCard = $this->generateDavCard($event);
		$this->manageDavCard($davCard);
	}


	/**
	 * @param GenericEvent $event
	 */
	public function onUpdateCard(GenericEvent $event) {
		$davCard = $this->generateDavCard($event);
		$this->manageDavCard($davCard);
	}


	/**
	 * @param GenericEvent $event
	 */
	public function onDeleteCard(GenericEvent $event) {
		$davCard = $this->generateDavCard($event, true);

		$this->miscService->log('Deleting Card: ' . json_encode($davCard), 1);
		$this->membersRequest->removeMembersByContactId($davCard->getUniqueId(), Member::TYPE_USER);
		$this->manageDeprecatedCircles($davCard->getAddressBookId());
		$this->manageDeprecatedMembers($davCard);
	}


	/**
	 * @param GenericEvent $event
	 * @param bool $tiny
	 *
	 * @return DavCard
	 */
	private function generateDavCard(GenericEvent $event, bool $tiny = false): DavCard {
		$addressBookId = $event->getArgument('addressBookId');
		$cardUri = $event->getArgument('cardUri');

		$davCard = new DavCard();
		$davCard->setAddressBookId($addressBookId);
		$davCard->setCardUri($cardUri);

		if ($tiny) {
			return $davCard;
		}

		$cardData = $event->getArgument('cardData');
		$davCard->setOwner($this->getOwnerFromAddressBook($addressBookId));
		$davCard->importFromDav($cardData);

		return $davCard;
	}


	/**
	 * @param int $bookId
	 * @param array $card
	 *
	 * @return DavCard
	 */
	private function generateDavCardFromCard(int $bookId, array $card): DavCard {
		$davCard = new DavCard();
		$davCard->setAddressBookId($bookId);
		$davCard->setCardUri($card['uri']);

		$davCard->setOwner($this->getOwnerFromAddressBook($bookId));
		$davCard->importFromDav($card['carddata']);

		return $davCard;
	}


	/**
	 * @param DavCard $davCard
	 *
	 */
	private function manageDavCard(DavCard $davCard) {
		$this->miscService->log('Updating Card: ' . json_encode($davCard), 1);
		$this->manageCircles($davCard);
		$this->manageContact($davCard);
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageContact(DavCard $davCard) {
		$this->manageDeprecatedMembers($davCard);

		switch ($this->getMemberType($davCard)) {
			case DavCard::TYPE_CONTACT:
				$this->manageRemoteContact($davCard);
				break;

			case DavCard::TYPE_LOCAL:
				$this->manageLocalContact($davCard);
				break;

//			case DavCard::TYPE_FEDERATED:
//				$this->manageFederatedContact($davCard);
//				break;
		}
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageDeprecatedMembers(DavCard $davCard) {
		$circles = array_map(
			function(Circle $circle) {
				return $circle->getUniqueId();
			}, $davCard->getCircles()
		);

		$members = $this->membersRequest->getMembersByContactId($davCard->getUniqueId());
		foreach ($members as $member) {
			if (!in_array($member->getCircleId(), $circles)) {
				$this->membersRequest->removeMember($member);
			}
		}
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageLocalContact(DavCard $davCard) {
		foreach ($davCard->getCircles() as $circle) {
			$this->manageMember($circle, $davCard, Member::TYPE_USER);
		}
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageRemoteContact(DavCard $davCard) {
		foreach ($davCard->getCircles() as $circle) {
			$this->manageMember($circle, $davCard, Member::TYPE_CONTACT);
		}
	}


	/**
	 * @param Circle $circle
	 * @param DavCard $davCard
	 * @param int $type
	 */
	private function manageMember(Circle $circle, DavCard $davCard, int $type) {
		try {
			$member =
				$this->membersRequest->getContactMember($circle->getUniqueId(), $davCard->getUniqueId());

			if ($member->getType() !== $type) {
				$this->membersRequest->removeMember($member);
				throw new MemberDoesNotExistException();
			}
		} catch (MemberDoesNotExistException $e) {
			$member = new Member();
			$member->setLevel(Member::LEVEL_MEMBER);
			$member->setStatus(Member::STATUS_MEMBER);
			$member->setContactId($davCard->getUniqueId());
			$member->setType($type);
			$member->setCircleId($circle->getUniqueId());
			$member->setUserId($davCard->getUserId());

			try {
				$this->membersRequest->createMember($member);

//				if ($type === Member::TYPE_CONTACT) {
//					$this->fileSharingBroadcaster->sendMailAboutExistingShares($circle, $member);
//				}
			} catch (MemberAlreadyExistsException $e) {
				$this->membersRequest->checkMember($member, false);
			}
		}
	}


	/**
	 * @param DavCard $davCard
	 *
	 * @return int
	 */
	private function getMemberType(DavCard $davCard): int {
		foreach (array_merge($davCard->getEmails(), $davCard->getClouds()) as $address) {
			try {
				$davCard->setUserId($this->isLocalMember($address));

				return DavCard::TYPE_LOCAL;
			} catch (NotLocalMemberException $e) {
			}
		}

		$davCard->setUserId($davCard->getOwner() . ':' . $davCard->getContactId());

		return DavCard::TYPE_CONTACT;
	}


	/**
	 * @param string $address
	 *
	 * @return string
	 * @throws NotLocalMemberException
	 */
	private function isLocalMember(string $address): string {
		if (strpos($address, '@') === false) {
			$user = $this->userManager->get($address);
			if ($user !== null) {
				return $user->getUID();
			}
			throw new NotLocalMemberException();
		}

		list ($username, $domain) = explode('@', $address);
		if (in_array($domain, $this->configService->getAvailableHosts())) {
			$user = $this->userManager->get($username);
			if ($user !== null) {
				return $user->getUID();
			}
		}

		throw new NotLocalMemberException();
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageCircles(DavCard $davCard) {
		$fromCard = $davCard->getGroups();
		$current = array_map(
			function(Circle $circle) {
				return $circle->getContactGroupName();
			}, $this->getCirclesFromBook($davCard->getAddressBookId())
		);

		$this->manageNewCircles($davCard, $fromCard, $current);
		$this->manageDeprecatedCircles($davCard->getAddressBookId());

		$this->assignCirclesToCard($davCard);
	}


	/**
	 * @param DavCard $davCard
	 * @param array $fromCard
	 * @param array $current
	 */
	private function manageNewCircles(DavCard $davCard, array $fromCard, array $current) {
		foreach ($fromCard as $group) {
			if (in_array($group, $current)) {
				continue;
			}

			$user = $this->userManager->get($davCard->getOwner());
			$circle = new Circle($this->configService->contactsBackendType(), $group . ' - ' . $user->getDisplayName());
			$circle->setContactAddressBook($davCard->getAddressBookId());
			$circle->setContactGroupName($group);

			try {
				$this->circlesRequest->createCircle($circle, $davCard->getOwner());
				$member = new Member($davCard->getOwner(), Member::TYPE_USER, $circle->getUniqueId());
				$member->setLevel(Member::LEVEL_OWNER);
				$member->setStatus(Member::STATUS_MEMBER);

				try {
					$this->membersRequest->createMember($member);
				} catch (MemberAlreadyExistsException $e) {
				}
			} catch (CircleAlreadyExistsException $e) {
			}

		}
	}


	/**
	 * @param DavCard $davCard
	 */
	private function assignCirclesToCard(DavCard $davCard) {
		foreach ($davCard->getGroups() as $group) {
			try {
				$davCard->addCircle(
					$this->circlesRequest->getFromContactGroup($davCard->getAddressBookId(), $group)
				);
			} catch (CircleDoesNotExistException $e) {
			}
		}
	}


	/**
	 * @param int $addressBookId
	 *
	 * @return Circle[]
	 */
	private function getCirclesFromBook(int $addressBookId): array {
		return $this->circlesRequest->getFromBook($addressBookId);
	}


	/**
	 * @param int $length
	 *
	 * @return string
	 * @deprecated
	 */
	protected function uuid(int $length = 0): string {
		$uuid = sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff), mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		if ($length > 0) {
			if ($length <= 16) {
				$uuid = str_replace('-', '', $uuid);
			}

			$uuid = substr($uuid, 0, $length);
		}

		return $uuid;
	}


	/**
	 * @param int $bookId
	 *
	 * @return string
	 */
	private function getOwnerFromAddressBook(int $bookId): string {
		$data = $this->cardDavBackend->getAddressBookById($bookId);

		// let's assume the format is principals/users/OWNER
		$owner = substr($data['principaluri'], 17);

		return $owner;
	}


	/**
	 *
	 * @throws Exception
	 */
	public function migration() {
		if (!$this->configService->isContactsBackend()) {
			throw new Exception('Circles needs to be set as Contacts App Backend first');
		}

		$this->manageDeprecatedContacts();
		$this->manageDeprecatedCircles();
		$users = $this->userManager->search('');
		foreach ($users as $user) {
			$books = $this->cardDavBackend->getAddressBooksForUser('principals/users/' . $user->getUID());
			foreach ($books as $book) {
				$this->migrateBook($book['id']);
			}
		}
	}


	/**
	 */
	private function manageDeprecatedContacts() {
		$contacts = $this->membersRequest->getMembersByContactId();

		foreach ($contacts as $contact) {
			try {
				$this->getDavCardFromMember($contact);
			} catch (MemberDoesNotExistException $e) {
				$this->membersRequest->removeMember($contact);
			}
		}
	}


	/**
	 * @param int $bookId
	 */
	private function manageDeprecatedCircles(int $bookId = 0) {
		$knownBooks = [$bookId];
		if ($bookId > 0) {
			$knownBooks = [];
			$contacts = $this->membersRequest->getMembersByContactId();
			foreach ($contacts as $contact) {
				list($bookId,) = explode('/', $contact->getContactId(), 2);
				if (in_array($bookId, $knownBooks)) {
					continue;
				}

				$knownBooks[] = $bookId;
			}
		}

		foreach ($knownBooks as $bookId) {
			$circles = $this->circlesRequest->getFromContactBook($bookId);
			$fromBook = $this->getExistingCirclesFromBook($bookId);

			foreach ($circles as $circle) {
				if (in_array($circle->getContactGroupName(), $fromBook)) {
					continue;
				}

				$this->membersRequest->removeAllFromCircle($circle->getUniqueId());
				$this->circlesRequest->destroyCircle($circle->getUniqueId());
			}
		}
	}


	/**
	 * @param int $bookId
	 *
	 * @return Circle[]
	 */
	private function getExistingCirclesFromBook(int $bookId): array {
		$circles = [];
		$cards = $this->cardDavBackend->getCards($bookId);
		foreach ($cards as $card) {
			$davCard = $this->generateDavCardFromCard($bookId, $card);
			$this->assignCirclesToCard($davCard);
			$circles = array_merge($circles, $davCard->getCircles());
		}

		$existing = array_map(
			function(Circle $circle) {
				return $circle->getContactGroupName();
			}, $circles
		);

		return array_unique($existing);
	}


	/**
	 * @param Member $contact
	 *
	 * @return DavCard
	 * @throws MemberDoesNotExistException
	 */
	public function getDavCardFromMember(Member $contact): DavCard {
		list($bookId, $cardUri) = explode('/', $contact->getContactId(), 2);
		$cards = $this->cardDavBackend->getCards($bookId);
		foreach ($cards as $card) {
			if ($card['uri'] === $cardUri) {
				return $this->generateDavCardFromCard($bookId, $card);
			}
		}

		throw new MemberDoesNotExistException();
	}


	/**
	 * @param int $bookId
	 */
	private function migrateBook(int $bookId) {
		if (in_array($bookId, $this->migratedBooks)) {
			return;
		}

		$owner = $this->getOwnerFromAddressBook($bookId);

		foreach ($this->cardDavBackend->getCards($bookId) as $card) {
			$davCard = new DavCard();
			$davCard->setOwner($owner);
			$davCard->importFromDav($card['carddata']);
			$davCard->setAddressBookId($bookId);
			$davCard->setCardUri($card['uri']);

			$this->manageDavCard($davCard);
		}

		$this->migratedBooks[] = $bookId;
	}


}


