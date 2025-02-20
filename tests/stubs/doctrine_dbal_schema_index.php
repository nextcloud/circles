<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_search;
use function array_shift;
use function count;
use function strtolower;

class Index extends AbstractAsset implements Constraint
{
    /**
     * Asset identifier instances of the column names the index is associated with.
     * array($columnName => Identifier)
     *
     * @var Identifier[]
     */
    protected $_columns = [];

    /** @var bool */
    protected $_isUnique = false;

    /** @var bool */
    protected $_isPrimary = false;

    /**
     * Platform specific flags for indexes.
     * array($flagName => true)
     *
     * @var true[]
     */
    protected $_flags = [];

    /**
     * @param string   $name
     * @param string[] $columns
     * @param bool     $isUnique
     * @param bool     $isPrimary
     * @param string[] $flags
     * @param mixed[]  $options
     */
    public function __construct($name, array $columns, $isUnique = false, $isPrimary = false, array $flags = [], array $options = [])
    {
    }

    /** @throws InvalidArgumentException */
    protected function _addColumn(string $column): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getColumns()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getQuotedColumns(AbstractPlatform $platform)
    {
    }

    /** @return string[] */
    public function getUnquotedColumns()
    {
    }

    /**
     * Is the index neither unique nor primary key?
     *
     * @return bool
     */
    public function isSimpleIndex()
    {
    }

    /** @return bool */
    public function isUnique()
    {
    }

    /** @return bool */
    public function isPrimary()
    {
    }

    /**
     * @param string $name
     * @param int    $pos
     *
     * @return bool
     */
    public function hasColumnAtPosition($name, $pos = 0)
    {
    }

    /**
     * Checks if this index exactly spans the given column names in the correct order.
     *
     * @param string[] $columnNames
     *
     * @return bool
     */
    public function spansColumns(array $columnNames)
    {
    }

    /**
     * Keeping misspelled function name for backwards compatibility
     *
     * @deprecated Use {@see isFulfilledBy()} instead.
     *
     * @return bool
     */
    public function isFullfilledBy(Index $other)
    {
    }

    /**
     * Checks if the other index already fulfills all the indexing and constraint needs of the current one.
     */
    public function isFulfilledBy(Index $other): bool
    {
    }

    /**
     * Detects if the other index is a non-unique, non primary index that can be overwritten by this one.
     *
     * @return bool
     */
    public function overrules(Index $other)
    {
    }

    /**
     * Returns platform specific flags for indexes.
     *
     * @return string[]
     */
    public function getFlags()
    {
    }

    /**
     * Adds Flag for an index that translates to platform specific handling.
     *
     * @param string $flag
     *
     * @return Index
     *
     * @example $index->addFlag('CLUSTERED')
     */
    public function addFlag($flag)
    {
    }

    /**
     * Does this index have a specific flag?
     *
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag($flag)
    {
    }

    /**
     * Removes a flag.
     *
     * @param string $flag
     *
     * @return void
     */
    public function removeFlag($flag)
    {
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
    }

    /** @return mixed[] */
    public function getOptions()
    {
    }
}
