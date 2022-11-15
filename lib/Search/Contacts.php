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
