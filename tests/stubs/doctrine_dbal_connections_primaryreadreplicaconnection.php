<?php

namespace Doctrine\DBAL\Connections;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Statement;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;
use SensitiveParameter;

use function array_rand;
use function count;

/**
 * Primary-Replica Connection
 *
 * Connection can be used with primary-replica setups.
 *
 * Important for the understanding of this connection should be how and when
 * it picks the replica or primary.
 *
 * 1. Replica if primary was never picked before and ONLY if 'getWrappedConnection'
 *    or 'executeQuery' is used.
 * 2. Primary picked when 'executeStatement', 'insert', 'delete', 'update', 'createSavepoint',
 *    'releaseSavepoint', 'beginTransaction', 'rollback', 'commit' or 'prepare' is called.
 * 3. If Primary was picked once during the lifetime of the connection it will always get picked afterwards.
 * 4. One replica connection is randomly picked ONCE during a request.
 *
 * ATTENTION: You can write to the replica with this connection if you execute a write query without
 * opening up a transaction. For example:
 *
 *      $conn = DriverManager::getConnection(...);
 *      $conn->executeQuery("DELETE FROM table");
 *
 * Be aware that Connection#executeQuery is a method specifically for READ
 * operations only.
 *
 * Use Connection#executeStatement for any SQL statement that changes/updates
 * state in the database (UPDATE, INSERT, DELETE or DDL statements).
 *
 * This connection is limited to replica operations using the
 * Connection#executeQuery operation only, because it wouldn't be compatible
 * with the ORM or SchemaManager code otherwise. Both use all the other
 * operations in a context where writes could happen to a replica, which makes
 * this restricted approach necessary.
 *
 * You can manually connect to the primary at any time by calling:
 *
 *      $conn->ensureConnectedToPrimary();
 *
 * Instantiation through the DriverManager looks like:
 *
 * @psalm-import-type Params from DriverManager
 * @example
 *
 * $conn = DriverManager::getConnection(array(
 *    'wrapperClass' => 'Doctrine\DBAL\Connections\PrimaryReadReplicaConnection',
 *    'driver' => 'pdo_mysql',
 *    'primary' => array('user' => '', 'password' => '', 'host' => '', 'dbname' => ''),
 *    'replica' => array(
 *        array('user' => 'replica1', 'password' => '', 'host' => '', 'dbname' => ''),
 *        array('user' => 'replica2', 'password' => '', 'host' => '', 'dbname' => ''),
 *    )
 * ));
 *
 * You can also pass 'driverOptions' and any other documented option to each of this drivers
 * to pass additional information.
 */
class PrimaryReadReplicaConnection extends Connection
{
    /**
     * Primary and Replica connection (one of the randomly picked replicas).
     *
     * @var DriverConnection[]|null[]
     */
    protected $connections = ['primary' => null, 'replica' => null];

    /**
     * You can keep the replica connection and then switch back to it
     * during the request if you know what you are doing.
     *
     * @var bool
     */
    protected $keepReplica = false;

    /**
     * Creates Primary Replica Connection.
     *
     * @internal The connection can be only instantiated by the driver manager.
     *
     * @param array<string,mixed> $params
     * @psalm-param Params $params
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function __construct(array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
    }

    /**
     * Checks if the connection is currently towards the primary or not.
     */
    public function isConnectedToPrimary(): bool
    {
    }

    /**
     * @param string|null $connectionName
     *
     * @return bool
     */
    public function connect($connectionName = null)
    {
    }

    protected function performConnect(?string $connectionName = null): bool
    {
    }

    /**
     * Connects to the primary node of the database cluster.
     *
     * All following statements after this will be executed against the primary node.
     */
    public function ensureConnectedToPrimary(): bool
    {
    }

    /**
     * Connects to a replica node of the database cluster.
     *
     * All following statements after this will be executed against the replica node,
     * unless the keepReplica option is set to false and a primary connection
     * was already opened.
     */
    public function ensureConnectedToReplica(): bool
    {
    }

    /**
     * Connects to a specific connection.
     *
     * @param string $connectionName
     *
     * @return DriverConnection
     *
     * @throws Exception
     */
    protected function connectTo($connectionName)
    {
    }

    /**
     * @param string  $connectionName
     * @param mixed[] $params
     *
     * @return mixed
     */
    protected function chooseConnectionConfiguration($connectionName, #[SensitiveParameter]
    $params)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createSavepoint($savepoint)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function releaseSavepoint($savepoint)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSavepoint($savepoint)
    {
    }

    public function prepare(string $sql): Statement
    {
    }
}
