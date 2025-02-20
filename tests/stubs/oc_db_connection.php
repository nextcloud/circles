<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Statement;
use OC\DB\QueryBuilder\Partitioned\PartitionedQueryBuilder;
use OC\DB\QueryBuilder\Partitioned\PartitionSplit;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\DB\QueryBuilder\Sharded\AutoIncrementHandler;
use OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper;
use OC\DB\QueryBuilder\Sharded\RoundRobinShardMapper;
use OC\DB\QueryBuilder\Sharded\ShardConnectionManager;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\Sharded\IShardMapper;
use OCP\Diagnostics\IEventLogger;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IRequestId;
use OCP\PreConditionNotMetException;
use OCP\Profiler\IProfiler;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use function count;
use function in_array;

class Connection extends PrimaryReadReplicaConnection {
	/** @var string */
	protected $tablePrefix;

	/** @var \OC\DB\Adapter $adapter */
	protected $adapter;

	protected $lockedTable = null;

	/** @var int */
	protected $queriesBuilt = 0;

	/** @var int */
	protected $queriesExecuted = 0;

	/** @var DbDataCollector|null */
	protected $dbDataCollector = null;

	protected ?float $transactionActiveSince = null;

	/** @var array<string, int> */
	protected $tableDirtyWrites = [];

	protected bool $logDbException = false;

	protected bool $logRequestId;
	protected string $requestId;

	/** @var array<string, list<string>> */
	protected array $partitions;
	/** @var ShardDefinition[] */
	protected array $shards = [];
	protected ShardConnectionManager $shardConnectionManager;
	protected AutoIncrementHandler $autoIncrementHandler;
	protected bool $isShardingEnabled;

	public const SHARD_PRESETS = [
		'filecache' => [
			'companion_keys' => [
				'file_id',
			],
			'companion_tables' => [
				'filecache_extended',
				'files_metadata',
			],
			'primary_key' => 'fileid',
			'shard_key' => 'storage',
			'table' => 'filecache',
		],
	];

	/**
	 * Initializes a new instance of the Connection class.
	 *
	 * @throws \Exception
	 */
	public function __construct(private array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
 {
 }

	/**
	 * @return IDBConnection[]
	 */
	public function getShardConnections(): array
 {
 }

	/**
	 * @throws Exception
	 */
	public function connect($connectionName = null)
 {
 }

	protected function performConnect(?string $connectionName = null): bool
 {
 }

	public function getStats(): array
 {
 }

	/**
	 * Returns a QueryBuilder for the connection.
	 */
	public function getQueryBuilder(): IQueryBuilder
 {
 }

	/**
	 * Gets the QueryBuilder for the connection.
	 *
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 * @deprecated 8.0.0 please use $this->getQueryBuilder() instead
	 */
	public function createQueryBuilder()
 {
 }

	/**
	 * Gets the ExpressionBuilder for the connection.
	 *
	 * @return \Doctrine\DBAL\Query\Expression\ExpressionBuilder
	 * @deprecated 8.0.0 please use $this->getQueryBuilder()->expr() instead
	 */
	public function getExpressionBuilder()
 {
 }

	/**
	 * Get the file and line that called the method where `getCallerBacktrace()` was used
	 *
	 * @return string
	 */
	protected function getCallerBacktrace()
 {
 }

	/**
	 * @return string
	 */
	public function getPrefix()
 {
 }

	/**
	 * Prepares an SQL statement.
	 *
	 * @param string $statement The SQL statement to prepare.
	 * @param int|null $limit
	 * @param int|null $offset
	 *
	 * @return Statement The prepared statement.
	 * @throws Exception
	 */
	public function prepare($sql, $limit = null, $offset = null): Statement
 {
 }

	/**
	 * Executes an, optionally parametrized, SQL query.
	 *
	 * If the query is parametrized, a prepared statement is used.
	 * If an SQLLogger is configured, the execution is logged.
	 *
	 * @param string $sql The SQL query to execute.
	 * @param array $params The parameters to bind to the query, if any.
	 * @param array $types The types the previous parameters are in.
	 * @param \Doctrine\DBAL\Cache\QueryCacheProfile|null $qcp The query cache profile, optional.
	 *
	 * @return Result The executed statement.
	 *
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
 {
 }

	/**
	 * @throws Exception
	 */
	public function executeUpdate(string $sql, array $params = [], array $types = []): int
 {
 }

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $sql The SQL query.
	 * @param array $params The query parameters.
	 * @param array $types The parameter types.
	 *
	 * @return int The number of affected rows.
	 *
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function executeStatement($sql, array $params = [], array $types = []): int
 {
 }

	protected function logQueryToFile(string $sql): void
 {
 }

	/**
	 * Returns the ID of the last inserted row, or the last value from a sequence object,
	 * depending on the underlying driver.
	 *
	 * Note: This method may not return a meaningful or consistent result across different drivers,
	 * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
	 * columns or sequences.
	 *
	 * @param string $seqName Name of the sequence object from which the ID should be returned.
	 *
	 * @return int the last inserted ID.
	 * @throws Exception
	 */
	public function lastInsertId($name = null): int
 {
 }

	/**
	 * @internal
	 * @throws Exception
	 */
	public function realLastInsertId($seqName = null)
 {
 }

	/**
	 * Insert a row if the matching row does not exists. To accomplish proper race condition avoidance
	 * it is needed that there is also a unique constraint on the values. Then this method will
	 * catch the exception and return 0.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *                            If this is null or an empty array, all keys of $input will be compared
	 *                            Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\Exception
	 * @deprecated 15.0.0 - use unique index and "try { $db->insert() } catch (UniqueConstraintViolationException $e) {}" instead, because it is more reliable and does not have the risk for deadlocks - see https://github.com/nextcloud/server/pull/12371
	 */
	public function insertIfNotExist($table, $input, ?array $compare = null)
 {
 }

	public function insertIgnoreConflict(string $table, array $values): int
 {
 }

	/**
	 * Insert or update a row value
	 *
	 * @param string $table
	 * @param array $keys (column name => value)
	 * @param array $values (column name => value)
	 * @param array $updatePreconditionValues ensure values match preconditions (column name => value)
	 * @return int number of new rows
	 * @throws \OCP\DB\Exception
	 * @throws PreConditionNotMetException
	 */
	public function setValues(string $table, array $keys, array $values, array $updatePreconditionValues = []): int
 {
 }

	/**
	 * Create an exclusive read+write lock on a table
	 *
	 * @param string $tableName
	 *
	 * @throws \BadMethodCallException When trying to acquire a second lock
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function lockTable($tableName)
 {
 }

	/**
	 * Release a previous acquired lock again
	 *
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function unlockTable()
 {
 }

	/**
	 * returns the error code and message as a string for logging
	 * works with DoctrineException
	 * @return string
	 */
	public function getError()
 {
 }

	public function errorCode()
 {
 }

	public function errorInfo()
 {
 }

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 *
	 * @throws Exception
	 */
	public function dropTable($table)
 {
 }

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function tableExists($table)
 {
 }

	protected function finishQuery(string $statement): string
 {
 }

	// internal use
	/**
	 * @param string $statement
	 * @return string
	 */
	protected function replaceTablePrefix($statement)
 {
 }

	/**
	 * Check if a transaction is active
	 *
	 * @return bool
	 * @since 8.2.0
	 */
	public function inTransaction()
 {
 }

	/**
	 * Escape a parameter to be used in a LIKE query
	 *
	 * @param string $param
	 * @return string
	 */
	public function escapeLikeParameter($param)
 {
 }

	/**
	 * Check whether or not the current database support 4byte wide unicode
	 *
	 * @return bool
	 * @since 11.0.0
	 */
	public function supports4ByteText()
 {
 }


	/**
	 * Create the schema of the connected database
	 *
	 * @return Schema
	 * @throws Exception
	 */
	public function createSchema()
 {
 }

	/**
	 * Migrate the database to the given schema
	 *
	 * @param Schema $toSchema
	 * @param bool $dryRun If true, will return the sql queries instead of running them.
	 *
	 * @throws Exception
	 *
	 * @return string|null Returns a string only if $dryRun is true.
	 */
	public function migrateToSchema(Schema $toSchema, bool $dryRun = false)
 {
 }

	public function beginTransaction()
 {
 }

	public function commit()
 {
 }

	public function rollBack()
 {
 }

	/**
	 * @return IDBConnection::PLATFORM_MYSQL|IDBConnection::PLATFORM_ORACLE|IDBConnection::PLATFORM_POSTGRES|IDBConnection::PLATFORM_SQLITE
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

	/**
	 * Log a database exception if enabled
	 *
	 * @param \Exception $exception
	 * @return void
	 */
	public function logDatabaseException(\Exception $exception): void
 {
 }

	public function getShardDefinition(string $name): ?ShardDefinition
 {
 }

	public function getCrossShardMoveHelper(): CrossShardMoveHelper
 {
 }
}
