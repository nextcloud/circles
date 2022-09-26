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


namespace OCA\Circles\Search;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\ISearch;

/**
 * Class FederatedUsers
 *
 * @package OCA\Circles\Search
 */
class FederatedUsers implements ISearch {
	private MemberRequest $memberRequest;

	/**
	 * LocalUsers constructor.
	 *
	 * @param MemberRequest $memberRequest
	 */
	public function __construct(MemberRequest $memberRequest) {
		$this->memberRequest = $memberRequest;
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(string $needle): array {
		return $this->memberRequest->searchFederatedUsers($needle);
	}
}
