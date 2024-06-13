<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

use OCA\Circles\Model\SearchResult;

/**
 * Interface ISearch
 *
 * @package OCA\Circles
 */
interface ISearch {
	/**
	 * @param string $needle
	 *
	 * @return list<SearchResult|IFederatedUser>
	 */
	public function search(string $needle): array;
}
