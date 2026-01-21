<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Mapping;

use OCP\HintException;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Server;
use OCP\Support\Subscription\IAssertion;

/**
 * Class UserMapping
 *
 * @package OCA\User_LDAP\Mapping
 */
class UserMapping extends AbstractMapping {

	protected const PROV_API_REGEX = '/\/ocs\/v[1-9].php\/cloud\/(groups|users)/';

	public function __construct(IDBConnection $dbc, ICacheFactory $cacheFactory, IAppConfig $config, bool $isCLI, private IAssertion $assertion)
 {
 }

	/**
	 * @throws HintException
	 */
	public function map($fdn, $name, $uuid): bool
 {
 }

	/**
	 * returns the DB table name which holds the mappings
	 * @return string
	 */
	protected function getTableName(bool $includePrefix = true)
 {
 }
}
