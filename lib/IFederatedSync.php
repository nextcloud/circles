<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

use OCA\Circles\Tools\Model\SimpleDataStore;

/**
 * Interface IFederatedSync
 *
 * @package OCA\Circles
 */
interface IFederatedSync {
	/**
	 * @param string $circleId
	 *
	 * @return SimpleDataStore
	 */
	public function export(string $circleId): SimpleDataStore;

	/**
	 * @param SimpleDataStore $data
	 */
	public function import(SimpleDataStore $data): void;
}
