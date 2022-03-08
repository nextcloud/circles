<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OC;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Model\Member;
use OCA\DAV\CardDAV\ContactsManager;
use OCP\Contacts\IManager;
use OCP\IAddressBook;
use OCP\IURLGenerator;

/**
 * Class ContactService
 *
 * @package OCA\Circles\Service
 */
class ContactService {
	use TArrayTools;
	use TStringTools;
	use TNCLogger;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ConfigService */
	private $configService;


	/**
	 * ContactService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param ConfigService $configService
	 */
	public function __construct(IURLGenerator $urlGenerator, ConfigService $configService) {
		$this->urlGenerator = $urlGenerator;
		$this->configService = $configService;
	}


	/**
	 * @param string $contactPath
	 *
	 * @return string
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 */
	public function getDisplayName(string $contactPath): string {
		$contact = $this->getContact($contactPath);

		if ($this->get('FN', $contact) !== '') {
			return $this->get('FN', $contact);
		}

		if ($this->get('EMAIL', $contact) !== '') {
			return $this->get('EMAIL', $contact);
		}

		if (!empty($this->getArray('EMAIL', $contact))) {
			return $this->getArray('EMAIL', $contact)[0];
		}

		// TODO: no idea if this situation might exists or if displaying the full contactPath is safe, so md5()
		return md5($contactPath);
	}


	/**
	 * @param string $contactPath
	 *
	 * @return array
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 */
	public function getMailAddresses(string $contactPath): array {
		$c = $this->getContact($contactPath);

		return ($this->get('EMAIL', $c) === '') ? [$this->get('EMAIL', $c)] : $this->getArray('EMAIL', $c);
	}


	/**
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws ContactAddressBookNotFoundException
	 */
	private function getContact(string $contactPath): array {
		[$userId, $addressBookUri, $contactId] = explode('/', $contactPath, 3);

		if ($userId === ''
			|| $contactId === ''
			|| $addressBookUri === ''
			|| is_null($contactId)
		) {
			throw new ContactFormatException('issue with contact format USERID/ADDRESSBOOK/CONTACTID');
		}

		$contactsManager = OC::$server->get(ContactsManager::class);
		$cm = OC::$server->get(IManager::class);
		$contactsManager->setupContactsProvider($cm, $userId, $this->urlGenerator);

		$addressBook = $this->getAddressBook($cm, $addressBookUri);
		$contacts = $addressBook->search(
			$contactId, ['UID'],
			[
				'types' => false,
				'escape_like_param' => false
			]
		);

		if (sizeof($contacts) !== 1) {
			throw new ContactNotFoundException();
		}

		return $contacts[0];
	}


	/**
	 * @param IManager $cm
	 * @param string $addressBookUri
	 *
	 * @return IAddressBook
	 * @throws ContactAddressBookNotFoundException
	 */
	private function getAddressBook(IManager $cm, string $addressBookUri): IAddressBook {
		foreach ($cm->getUserAddressBooks() as $addressBook) {
			if ($addressBook->getUri() === $addressBookUri) {
				return $addressBook;
			}
		}

		throw new ContactAddressBookNotFoundException();
	}


	/**
	 * @param IManager $cm
	 * @param string $addressBookKey
	 *
	 * @return IAddressBook
	 * @throws ContactAddressBookNotFoundException
	 */
	public function getAddressBoxById(IManager $cm, string $addressBookKey): IAddressBook {
		foreach ($cm->getUserAddressBooks() as $addressBook) {
			if ($addressBook->getKey() === $addressBookKey) {
				return $addressBook;
			}
		}

		throw new ContactAddressBookNotFoundException();
	}


	/**
	 * @param Member $member
	 *
	 * @return array
	 */
	public function getMailAddressesFromMember(Member $member): array {
		if ($member->getUserType() !== Member::TYPE_CONTACT
			|| !$this->configService->isLocalInstance($member->getInstance())) {
			return [];
		}

		try {
			return $this->getMailAddresses($member->getUserId());
		} catch (Exception $e) {
			return [];
		}
	}
}
