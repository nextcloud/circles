<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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

declare(strict_types=1);

namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version0017Date20200202112901 extends SimpleMigrationStep {


	/** @var IDBConnection */
	private $connection;


	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('circles_mb_tmp');
		$table->addColumn(
			'circle_id', 'string', [
						   'notnull' => true,
						   'length'  => 64,
					   ]
		);
		$table->addColumn(
			'user_id', 'string', [
						 'notnull' => true,
						 'length'  => 128,
					 ]
		);

//			$table->addColumn(
//				'instance', 'string', [
//							  'notnull' => false,
//							  'length'  => 255,
//						  ]
//			);
//			$table->setPrimaryKey(['circle_id', 'user_id', 'user_type', 'instance']);

		$table->addColumn(
			'user_type', 'smallint', [
						   'notnull' => true,
						   'length'  => 1,
						   'default' => 1,
					   ]
		);
		$table->addColumn(
			'level', 'smallint', [
					   'notnull' => true,
					   'length'  => 1,
				   ]
		);
		$table->addColumn(
			'status', 'string', [
						'notnull' => false,
						'length'  => 15,
					]
		);
		$table->addColumn(
			'note', 'string', [
					  'notnull' => false,
					  'length'  => 255,
				  ]
		);
		$table->addColumn(
			'joined', 'datetime', [
						'notnull' => false,
					]
		);
		$table->addColumn(
			'member_id', Type::STRING, [
						   'notnull' => false,
						   'length'  => 15,
					   ]
		);
		$table->addColumn(
			'contact_meta', 'string', [
							  'notnull' => false,
							  'length'  => 1000,
						  ]
		);
		$table->addColumn(
			'contact_checked', Type::SMALLINT, [
								 'notnull' => false,
								 'length'  => 1,
							 ]
		);
		$table->addColumn(
			'contact_id', 'string', [
							'notnull' => false,
							'length'  => 127,
						]
		);

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$this->copyTable('circles_members', 'circles_mb_tmp');
	}


	protected function copyTable($orig, $dest) {
		$connection = \OC::$server->getDatabaseConnection();
		$qb = $connection->getQueryBuilder();

		$qb->select('*')
		   ->from($orig);

		$result = $qb->execute();
		while ($row = $result->fetch()) {

			$copy = $connection->getQueryBuilder();
			$copy->insert($dest);
			$ak = array_keys($row);
			foreach ($ak as $k) {
				if ($row[$k] !== null) {
					$copy->setValue($k, $copy->createNamedParameter($row[$k]));
				}
			}
			$copy->execute();
		}

	}

}
