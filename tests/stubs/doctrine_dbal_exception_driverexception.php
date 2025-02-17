<?php

namespace Doctrine\DBAL\Exception;

use Doctrine\DBAL\Driver\Exception as TheDriverException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query;

use function assert;

/**
 * Base class for all errors detected in the driver.
 *
 * @psalm-immutable
 */
class DriverException extends Exception implements TheDriverException
{
    /**
     * @internal
     *
     * @param TheDriverException $driverException The DBAL driver exception to chain.
     * @param Query|null         $query           The SQL query that triggered the exception, if any.
     */
    public function __construct(TheDriverException $driverException, ?Query $query)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLState()
    {
    }

    public function getQuery(): ?Query
    {
    }
}
