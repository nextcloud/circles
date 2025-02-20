<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OCP\DB\ISchemaWrapper;

class SchemaWrapper implements ISchemaWrapper {
	/** @var Connection */
	protected $connection;

	/** @var Schema */
	protected $schema;

	/** @var array */
	protected $tablesToDelete = [];

	public function __construct(Connection $connection, ?Schema $schema = null)
 {
 }

	public function getWrappedSchema()
 {
 }

	public function performDropTableCalls()
 {
 }

	/**
	 * Gets all table names
	 *
	 * @return array
	 */
	public function getTableNamesWithoutPrefix()
 {
 }

	// Overwritten methods

	/**
	 * @return array
	 */
	public function getTableNames()
 {
 }

	/**
	 * @param string $tableName
	 *
	 * @return \Doctrine\DBAL\Schema\Table
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function getTable($tableName)
 {
 }

	/**
	 * Does this schema have a table with the given name?
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function hasTable($tableName)
 {
 }

	/**
	 * Creates a new table.
	 *
	 * @param string $tableName
	 * @return \Doctrine\DBAL\Schema\Table
	 */
	public function createTable($tableName)
 {
 }

	/**
	 * Drops a table from the schema.
	 *
	 * @param string $tableName
	 * @return \Doctrine\DBAL\Schema\Schema
	 */
	public function dropTable($tableName)
 {
 }

	/**
	 * Gets all tables of this schema.
	 *
	 * @return \Doctrine\DBAL\Schema\Table[]
	 */
	public function getTables()
 {
 }

	/**
	 * Gets the DatabasePlatform for the database.
	 *
	 * @return AbstractPlatform
	 *
	 * @throws Exception
	 */
	public function getDatabasePlatform()
 {
 }
}
