<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Adapts the public API to our internal DBAL connection wrapper
 */
class ConnectionAdapter implements IDBConnection {
	public function __construct(Connection $inner)
 {
 }

	public function getQueryBuilder(): IQueryBuilder
 {
 }

	public function prepare($sql, $limit = null, $offset = null): IPreparedStatement
 {
 }

	public function executeQuery(string $sql, array $params = [], $types = []): IResult
 {
 }

	public function executeUpdate(string $sql, array $params = [], array $types = []): int
 {
 }

	public function executeStatement($sql, array $params = [], array $types = []): int
 {
 }

	public function lastInsertId(string $table): int
 {
 }

	public function insertIfNotExist(string $table, array $input, ?array $compare = null)
 {
 }

	public function insertIgnoreConflict(string $table, array $values): int
 {
 }

	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int
 {
 }

	public function lockTable($tableName): void
 {
 }

	public function unlockTable(): void
 {
 }

	public function beginTransaction(): void
 {
 }

	public function inTransaction(): bool
 {
 }

	public function commit(): void
 {
 }

	public function rollBack(): void
 {
 }

	public function getError(): string
 {
 }

	public function errorCode()
 {
 }

	public function errorInfo()
 {
 }

	public function connect(): bool
 {
 }

	public function close(): void
 {
 }

	public function quote($input, $type = IQueryBuilder::PARAM_STR)
 {
 }

	/**
	 * @todo we are leaking a 3rdparty type here
	 */
	public function getDatabasePlatform(): AbstractPlatform
 {
 }

	public function dropTable(string $table): void
 {
 }

	public function tableExists(string $table): bool
 {
 }

	public function escapeLikeParameter(string $param): string
 {
 }

	public function supports4ByteText(): bool
 {
 }

	/**
	 * @todo leaks a 3rdparty type
	 */
	public function createSchema(): Schema
 {
 }

	public function migrateToSchema(Schema $toSchema): void
 {
 }

	public function getInner(): Connection
 {
 }

	/**
	 * @return self::PLATFORM_MYSQL|self::PLATFORM_ORACLE|self::PLATFORM_POSTGRES|self::PLATFORM_SQLITE
	 */
	public function getDatabaseProvider(): string
 {
 }

	/**
	 * @internal Should only be used inside the QueryBuilder, ExpressionBuilder and FunctionBuilder
	 * All apps and API code should not need this and instead use provided functionality from the above.
	 */
	public function getServerVersion(): string
 {
 }

	public function logDatabaseException(\Exception $exception)
 {
 }

	public function getShardDefinition(string $name): ?ShardDefinition
 {
 }

	public function getCrossShardMoveHelper(): CrossShardMoveHelper
 {
 }
}
