<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0024Date20220203123902 extends SimpleMigrationStep {
	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_event')) {
			$table = $schema->createTable('circles_event');
			$table->addColumn(
				'token', 'string', [
					'notnull' => false,
					'length' => 63,
				]
			);
			$table->addColumn(
				'event', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'result', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'length' => 255,
					'notnull' => false
				]
			);
			$table->addColumn(
				'interface', 'integer', [
					'notnull' => true,
					'length' => 1,
					'default' => 0
				]
			);
			$table->addColumn(
				'severity', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'retry', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'status', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'updated', 'datetime', [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'creation', 'bigint', [
					'length' => 14,
					'notnull' => false
				]
			);

			$table->setPrimaryKey(['token', 'instance']);
		}

		return $schema;
	}
}
