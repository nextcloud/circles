<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Mapping;

use OCP\HintException;
use OCP\IDBConnection;
use OCP\Support\Subscription\IAssertion;

/**
 * Class UserMapping
 *
 * @package OCA\User_LDAP\Mapping
 */
class UserMapping extends AbstractMapping {

	public function __construct(
		IDBConnection $dbc,
		private IAssertion $assertion,
	) {
		parent::__construct($dbc);
	}

	/**
	 * @throws HintException
	 */
	public function map($fdn, $name, $uuid): bool {
	}

	/**
	 * returns the DB table name which holds the mappings
	 * @return string
	 */
	protected function getTableName(bool $includePrefix = true) {
	}
}
