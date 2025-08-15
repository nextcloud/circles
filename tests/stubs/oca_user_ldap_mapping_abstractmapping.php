<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Mapping;

use OCP\DB\IPreparedStatement;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class AbstractMapping
 *
 * @package OCA\User_LDAP\Mapping
 */
abstract class AbstractMapping {
	/**
	 * returns the DB table name which holds the mappings
	 *
	 * @return string
	 */
	abstract protected function getTableName(bool $includePrefix = true);

	/**
	 * @param IDBConnection $dbc
	 */
	public function __construct(
		protected IDBConnection $dbc,
	) {
	}

	/** @var array caches Names (value) by DN (key) */
	protected $cache = [];

	/**
	 * checks whether a provided string represents an existing table col
	 *
	 * @param string $col
	 * @return bool
	 */
	public function isColNameValid($col) {
	}

	/**
	 * Gets the value of one column based on a provided value of another column
	 *
	 * @param string $fetchCol
	 * @param string $compareCol
	 * @param string $search
	 * @return string|false
	 * @throws \Exception
	 */
	protected function getXbyY($fetchCol, $compareCol, $search) {
	}

	/**
	 * Performs a DELETE or UPDATE query to the database.
	 *
	 * @param IPreparedStatement $statement
	 * @param array $parameters
	 * @return bool true if at least one row was modified, false otherwise
	 */
	protected function modify(IPreparedStatement $statement, $parameters) {
	}

	/**
	 * Gets the LDAP DN based on the provided name.
	 * Replaces Access::ocname2dn
	 *
	 * @param string $name
	 * @return string|false
	 */
	public function getDNByName($name) {
	}

	/**
	 * Updates the DN based on the given UUID
	 *
	 * @param string $fdn
	 * @param string $uuid
	 * @return bool
	 */
	public function setDNbyUUID($fdn, $uuid) {
	}

	/**
	 * Updates the UUID based on the given DN
	 *
	 * required by Migration/UUIDFix
	 *
	 * @param $uuid
	 * @param $fdn
	 * @return bool
	 */
	public function setUUIDbyDN($uuid, $fdn): bool {
	}

	/**
	 * Get the hash to store in database column ldap_dn_hash for a given dn
	 */
	protected function getDNHash(string $fdn): string {
	}

	/**
	 * Gets the name based on the provided LDAP DN.
	 *
	 * @param string $fdn
	 * @return string|false
	 */
	public function getNameByDN($fdn) {
	}

	/**
	 * @param array<string> $hashList
	 */
	protected function prepareListOfIdsQuery(array $hashList): IQueryBuilder {
	}

	protected function collectResultsFromListOfIdsQuery(IQueryBuilder $qb, array &$results): void {
	}

	/**
	 * @param array<string> $fdns
	 * @return array<string,string>
	 */
	public function getListOfIdsByDn(array $fdns): array {
	}

	/**
	 * Searches mapped names by the giving string in the name column
	 *
	 * @return string[]
	 */
	public function getNamesBySearch(string $search, string $prefixMatch = '', string $postfixMatch = ''): array {
	}

	/**
	 * Gets the name based on the provided LDAP UUID.
	 *
	 * @param string $uuid
	 * @return string|false
	 */
	public function getNameByUUID($uuid) {
	}

	public function getDnByUUID($uuid) {
	}

	/**
	 * Gets the UUID based on the provided LDAP DN
	 *
	 * @param string $dn
	 * @return false|string
	 * @throws \Exception
	 */
	public function getUUIDByDN($dn) {
	}

	public function getList(int $offset = 0, ?int $limit = null, bool $invalidatedOnly = false): array {
	}

	/**
	 * attempts to map the given entry
	 *
	 * @param string $fdn fully distinguished name (from LDAP)
	 * @param string $name
	 * @param string $uuid a unique identifier as used in LDAP
	 * @return bool
	 */
	public function map($fdn, $name, $uuid) {
	}

	/**
	 * removes a mapping based on the owncloud_name of the entry
	 *
	 * @param string $name
	 * @return bool
	 */
	public function unmap($name) {
	}

	/**
	 * Truncates the mapping table
	 *
	 * @return bool
	 */
	public function clear() {
	}

	/**
	 * clears the mapping table one by one and executing a callback with
	 * each row's id (=owncloud_name col)
	 *
	 * @param callable $preCallback
	 * @param callable $postCallback
	 * @return bool true on success, false when at least one row was not
	 *              deleted
	 */
	public function clearCb(callable $preCallback, callable $postCallback): bool {
	}

	/**
	 * returns the number of entries in the mappings table
	 *
	 * @return int
	 */
	public function count(): int {
	}

	public function countInvalidated(): int {
	}
}
