<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Exception\ColumnAlreadyExists;
use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use Doctrine\DBAL\Schema\Exception\ForeignKeyDoesNotExist;
use Doctrine\DBAL\Schema\Exception\IndexAlreadyExists;
use Doctrine\DBAL\Schema\Exception\IndexDoesNotExist;
use Doctrine\DBAL\Schema\Exception\IndexNameInvalid;
use Doctrine\DBAL\Schema\Exception\NamedForeignKeyRequired;
use Doctrine\DBAL\Schema\Exception\NamespaceAlreadyExists;
use Doctrine\DBAL\Schema\Exception\SequenceAlreadyExists;
use Doctrine\DBAL\Schema\Exception\SequenceDoesNotExist;
use Doctrine\DBAL\Schema\Exception\TableAlreadyExists;
use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Schema\Exception\UniqueConstraintDoesNotExist;

use function sprintf;

/** @psalm-immutable */
class SchemaException extends Exception
{
    /** @deprecated Use {@see TableDoesNotExist} instead. */
    public const TABLE_DOESNT_EXIST = 10;

    /** @deprecated Use {@see TableAlreadyExists} instead. */
    public const TABLE_ALREADY_EXISTS = 20;

    /** @deprecated Use {@see ColumnDoesNotExist} instead. */
    public const COLUMN_DOESNT_EXIST = 30;

    /** @deprecated Use {@see ColumnAlreadyExists} instead. */
    public const COLUMN_ALREADY_EXISTS = 40;

    /** @deprecated Use {@see IndexDoesNotExist} instead. */
    public const INDEX_DOESNT_EXIST = 50;

    /** @deprecated Use {@see IndexAlreadyExists} instead. */
    public const INDEX_ALREADY_EXISTS = 60;

    /** @deprecated Use {@see SequenceDoesNotExist} instead. */
    public const SEQUENCE_DOENST_EXIST = 70;

    /** @deprecated Use {@see SequenceAlreadyExists} instead. */
    public const SEQUENCE_ALREADY_EXISTS = 80;

    /** @deprecated Use {@see IndexNameInvalid} instead. */
    public const INDEX_INVALID_NAME = 90;

    /** @deprecated Use {@see ForeignKeyDoesNotExist} instead. */
    public const FOREIGNKEY_DOESNT_EXIST = 100;

    /** @deprecated Use {@see UniqueConstraintDoesNotExist} instead. */
    public const CONSTRAINT_DOESNT_EXIST = 110;

    /** @deprecated Use {@see NamespaceAlreadyExists} instead. */
    public const NAMESPACE_ALREADY_EXISTS = 120;

    /**
     * @param string $tableName
     *
     * @return SchemaException
     */
    public static function tableDoesNotExist($tableName)
    {
    }

    /**
     * @param string $indexName
     *
     * @return SchemaException
     */
    public static function indexNameInvalid($indexName)
    {
    }

    /**
     * @param string $indexName
     * @param string $table
     *
     * @return SchemaException
     */
    public static function indexDoesNotExist($indexName, $table)
    {
    }

    /**
     * @param string $indexName
     * @param string $table
     *
     * @return SchemaException
     */
    public static function indexAlreadyExists($indexName, $table)
    {
    }

    /**
     * @param string $columnName
     * @param string $table
     *
     * @return SchemaException
     */
    public static function columnDoesNotExist($columnName, $table)
    {
    }

    /**
     * @param string $namespaceName
     *
     * @return SchemaException
     */
    public static function namespaceAlreadyExists($namespaceName)
    {
    }

    /**
     * @param string $tableName
     *
     * @return SchemaException
     */
    public static function tableAlreadyExists($tableName)
    {
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return SchemaException
     */
    public static function columnAlreadyExists($tableName, $columnName)
    {
    }

    /**
     * @param string $name
     *
     * @return SchemaException
     */
    public static function sequenceAlreadyExists($name)
    {
    }

    /**
     * @param string $name
     *
     * @return SchemaException
     */
    public static function sequenceDoesNotExist($name)
    {
    }

    /**
     * @param string $constraintName
     * @param string $table
     *
     * @return SchemaException
     */
    public static function uniqueConstraintDoesNotExist($constraintName, $table)
    {
    }

    /**
     * @param string $fkName
     * @param string $table
     *
     * @return SchemaException
     */
    public static function foreignKeyDoesNotExist($fkName, $table)
    {
    }

    /** @return SchemaException */
    public static function namedForeignKeyRequired(Table $localTable, ForeignKeyConstraint $foreignKey)
    {
    }

    /**
     * @param string $changeName
     *
     * @return SchemaException
     */
    public static function alterTableChangeNotSupported($changeName)
    {
    }
}
