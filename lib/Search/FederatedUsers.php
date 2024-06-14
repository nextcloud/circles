<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
