<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version0023Date20211216113101
 *
 * @package OCA\Circles\Migration
 */
class Version0023Date20211216113101 extends SimpleMigrationStep {
	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
	}
}
