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
class Version0017Date20191206144441 extends SimpleMigrationStep {


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

		$table = $schema->getTable('circles_circles');
		if (!$table->hasColumn('contact_addressbook')) {
			$table->addColumn(
				'contact_addressbook', 'integer', [
										 'notnull'  => false,
										 'unsigned' => true,
										 'length'   => 7,
									 ]
			);
		}
		if (!$table->hasColumn('contact_groupname')) {
			$table->addColumn(
				'contact_groupname', 'string', [
									   'notnull' => false,
									   'length'  => 127,
								   ]
			);
		}

		$table = $schema->getTable('circles_members');
		if (!$table->hasColumn('member_id')) {
			$table->addColumn(
				'member_id', Type::STRING, [
							   'notnull' => false,
							   'length'  => 15,
						   ]
			);
		}
		if (!$table->hasColumn('contact_meta')) {
			$table->addColumn(
				'contact_meta', 'string', [
								  'notnull' => false,
								  'length'  => 1000,
							  ]
			);
		}
		if (!$table->hasColumn('contact_checked')) {
			$table->addColumn(
				'contact_checked', Type::SMALLINT, [
									 'notnull' => false,
									 'length'  => 1,
								 ]
			);
		}
		if (!$table->hasColumn('contact_id')) {
			$table->addColumn(
				'contact_id', 'string', [
								'notnull' => false,
								'length'  => 127,
							]
			);
			if ($table->hasPrimaryKey()) {
				$table->dropPrimaryKey();
			}
			$table->setPrimaryKey(['circle_id', 'user_id', 'contact_id']);
		}

		$table = $schema->getTable('circles_tokens');
		if (!$table->hasColumn('member_id')) {
			$table->addColumn(
				'member_id', Type::STRING, [
							   'notnull' => false,
							   'length'  => 15,
						   ]
			);
		}
		if (!$table->hasColumn('accepted')) {
			$table->addColumn(
				'accepted', Type::SMALLINT, [
							  'notnull' => false,
							  'length'  => 1,
						  ]
			);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {

		$qb = $this->connection->getQueryBuilder();
		$expr = $qb->expr();

		$orX = $expr->orX();
		$orX->add($expr->eq('member_id', $qb->createNamedParameter('')));
		$orX->add($expr->isNull('member_id'));

		$qb->select('circle_id', 'user_id', 'user_type')
		   ->from('circles_members')
		   ->where($orX);

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$uniqueId = substr(bin2hex(openssl_random_pseudo_bytes(24)), 0, 15);

			$update = $this->connection->getQueryBuilder();
			$expru = $update->expr();
			$update->update('circles_members')
				   ->set('member_id', $update->createNamedParameter($uniqueId))
				   ->where($expru->eq('circle_id', $update->createNamedParameter($row['circle_id'])))
				   ->andWhere($expru->eq('user_id', $update->createNamedParameter($row['user_id'])))
				   ->andWhere($expru->eq('user_type', $update->createNamedParameter($row['user_type'])));

			$update->execute();
		}


		$qb2 = $this->connection->getQueryBuilder();
		$expr2 = $qb2->expr();
		$orX = $expr2->orX();
		$orX->add($expr2->eq('member_id', $qb2->createNamedParameter('')));
		$orX->add($expr2->isNull('member_id'));
		$qb2->select('user_id', 'circle_id')
			->from('circles_tokens')
			->where($orX);

		$result = $qb2->execute();
		while ($row = $result->fetch()) {
			$qbm = $this->connection->getQueryBuilder();
			$exprm = $qbm->expr();

			$qbm->select('member_id')
				->from('circles_members')
				->where($exprm->eq('circle_id', $qbm->createNamedParameter($row['circle_id'])))
				->andWhere($exprm->eq('user_id', $qbm->createNamedParameter($row['user_id'])))
				->andWhere($exprm->neq('user_type', $qbm->createNamedParameter('1')));

			$resultm = $qbm->execute();
			$member = $resultm->fetch();

			$update = $this->connection->getQueryBuilder();
			$expru = $update->expr();
			$update->update('circles_tokens')
				   ->set('member_id', $update->createNamedParameter($member['member_id']))
				   ->where($expru->eq('circle_id', $update->createNamedParameter($row['circle_id'])))
				   ->andWhere($expru->eq('user_id', $update->createNamedParameter($row['user_id'])));

			$update->execute();
		}

	}
}
