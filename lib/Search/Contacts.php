<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Search;

use OCA\Circles\ISearch;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\MiscService;

class Contacts implements ISearch {
	/**
	 * {@inheritdoc}
	 */
	public function search($needle): array {
		$result = [];
		$contactManager = \OC::$server->getContactsManager();

		// Add 'ADR' to search also in the address
		$contacts = $contactManager->search($needle, ['FN', 'ORG', 'EMAIL']);
		foreach ($contacts as $contact) {
			if (($contact['isLocalSystemBook'] ?? false) === true) {
				continue;
			}

			$data = $this->generateDataArray($contact);
			$result[] = new SearchResult($contact['UID'], DeprecatedMember::TYPE_CONTACT, '', $data);
		}

		return $result;
	}


	/**
	 * @param array $contact
	 *
	 * @return array
	 */
	private function generateDataArray($contact) {
		$data = [
			'display' => '',
			'email' => '',
			'organization' => ''
		];

		$data['display'] = $data['email'] = MiscService::get($contact, 'EMAIL');
		$data['display'] = MiscService::get($contact, 'FN', $data['display']);
		$data['organization'] = MiscService::get($contact, 'ORG');

		return $data;
	}
}
