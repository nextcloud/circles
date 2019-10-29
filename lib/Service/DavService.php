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
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\GenericEvent;


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
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IUserManager $userManager, CardDavBackend $cardDavBackend, CirclesRequest $circlesRequest,
		MembersRequest $membersRequest, ConfigService $configService, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->cardDavBackend = $cardDavBackend;
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

		$this->migration();
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
		$addressBookId = $event->getArgument('addressBookId');
		$cardUri = $event->getArgument('cardUri');
	}


	/**
	 * @param GenericEvent $event
	 *
	 * @return DavCard
	 */
	private function generateDavCard(GenericEvent $event): DavCard {
		$addressBookId = $event->getArgument('addressBookId');
		$cardUri = $event->getArgument('cardUri');
		$cardData = $event->getArgument('cardData');

		$davCard = new DavCard();
		$davCard->setOwner($this->getOwnerFromAddressBook($addressBookId));
		$davCard->importFromDav($cardData);
		$davCard->setAddressBookId($addressBookId);
		$davCard->setCardUri($cardUri);

		return $davCard;
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageDavCard(DavCard $davCard) {
		$this->miscService->log('Updating Card: ' . json_encode($davCard));
		$this->manageCircles($davCard);
		$this->manageContact($davCard);
	}


	/**
	 * @param DavCard $davCard
	 */
	private function manageContact(DavCard $davCard) {
		$this->manageDeprecatedMembers($davCard);

		try {
			$userId = $this->isLocalMember($davCard);

			$this->manageLocalContact($davCard, $userId);
		} catch (NotLocalMemberException $e) {
			$this->manageRemoteContact($davCard);
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

		$members = $this->membersRequest->getMembersByContactId($davCard->getContactId());
		foreach ($members as $member) {
			if (!in_array($member->getCircleId(), $circles)) {
				$this->membersRequest->removeMember($member);
			}
		}
	}


	/**
	 * @param DavCard $davCard
	 * @param string $userId
	 */
	private function manageLocalContact(DavCard $davCard, string $userId) {
		foreach ($davCard->getCircles() as $circle) {
			$this->manageMember($circle, $davCard, Member::TYPE_USER, $userId);
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
	 * @param string $userId
	 */
	private function manageMember(Circle $circle, DavCard $davCard, int $type, string $userId = '') {
		if ($userId === '') {
			$userId = $davCard->getOwner() . ':' . $davCard->getContactId();
		}

		try {
			$member =
				$this->membersRequest->getContactMember($circle->getUniqueId(), $davCard->getContactId());

			if ($member->getType() !== $type) {
				$this->membersRequest->removeMember($member);
				throw new MemberDoesNotExistException();
			}
		} catch (MemberDoesNotExistException $e) {
			$member = new Member();
			$member->setLevel(Member::LEVEL_MEMBER);
			$member->setStatus(Member::STATUS_MEMBER);
			$member->setContactId($davCard->getContactId());
			$member->setType($type);
			$member->setCircleId($circle->getUniqueId());
			$member->setUserId($userId);

			try {
				$this->membersRequest->createMember($member);
			} catch (MemberAlreadyExistsException $e) {
			}
		}
	}


	/**
	 * @param DavCard $davCard
	 *
	 * @return string
	 * @throws NotLocalMemberException
	 */
	private function isLocalMember(DavCard $davCard): string {
		foreach ($davCard->getEmails() as $email) {
			if (strpos($email, '@') === false) {
				continue;
			}

			list ($username, $domain) = explode('@', $email);
			if (in_array($domain, $this->configService->getAvailableHosts())) {
				$user = $this->userManager->get($username);
				if ($user !== null) {
					return $user->getUID();
				}
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
		$this->manageDeprecatedCircles($fromCard, $current);

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

			$circle = new Circle(Circle::CIRCLES_PUBLIC, $group . ' - ' . $this->uuid(5));
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
	 * // TODO: Get all group from an addressbook
	 * // TODO: remove deprecated circles
	 *
	 * @param array $fromCard
	 * @param array $current
	 */
	private function manageDeprecatedCircles(array $fromCard, array $current) {
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
	 */
	private function migration() {
		$users = $this->userManager->search('');
		foreach ($users as $user) {
			$books = $this->cardDavBackend->getAddressBooksForUser('principals/users/' . $user->getUID());
			foreach ($books as $book) {
				$this->migrateBook($book['id']);
			}
		}
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


