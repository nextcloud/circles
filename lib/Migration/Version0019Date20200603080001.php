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
class Version0019Date20200603080001 extends SimpleMigrationStep {


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
	 *
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circle_circles')) {
			$table = $schema->createTable('circle_circles');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 4,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'unique_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'long_id', 'string', [
							 'notnull' => true,
							 'length'  => 64,
						 ]
			);
			$table->addColumn(
				'name', 'string', [
						  'notnull' => true,
						  'length'  => 127,
					  ]
			);
			$table->addColumn(
				'alt_name', 'string', [
							  'notnull' => false,
							  'length'  => 127,
							  'default' => ''
						  ]
			);
			$table->addColumn(
				'description', 'text', [
								 'notnull' => false
							 ]
			);
			$table->addColumn(
				'settings', 'text', [
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'type', 'smallint', [
						  'notnull' => true,
						  'length'  => 2,
					  ]
			);
			$table->addColumn(
				'creation', 'datetime', [
							  'notnull' => false,
						  ]
			);
			$table->addColumn(
				'contact_addressbook', 'integer', [
										 'notnull'  => false,
										 'unsigned' => true,
										 'length'   => 7,
									 ]
			);
			$table->addColumn(
				'contact_groupname', 'string', [
									   'notnull' => false,
									   'length'  => 127,
								   ]
			);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['unique_id']);
			$table->addUniqueIndex(['long_id']);
			$table->addIndex(['type']);
		}


		if (!$schema->hasTable('circle_clouds')) {
			$table = $schema->createTable('circle_clouds');
			$table->addColumn(
				'cloud_id', 'string', [
							  'notnull' => true,
							  'length'  => 64,
						  ]
			);
			$table->addColumn(
				'address', 'string', [
							 'notnull' => true,
							 'length'  => 255,
						 ]
			);
			$table->addColumn(
				'status', 'smallint', [
							'notnull' => true,
							'length'  => 1,
						]
			);
			$table->addColumn(
				'note', 'text', [
						  'notnull' => false
					  ]
			);
			$table->addColumn(
				'created', 'datetime', [
							 'notnull' => false,
						 ]
			);
			$table->setPrimaryKey(['cloud_id']);
		}


		if (!$schema->hasTable('circle_groups')) {
			$table = $schema->createTable('circle_groups');
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'group_id', 'string', [
							  'notnull' => true,
							  'length'  => 64,
						  ]
			);
			$table->addColumn(
				'level', 'smallint', [
						   'notnull' => true,
						   'length'  => 1,
					   ]
			);
			$table->addColumn(
				'note', 'text', [
						  'notnull' => false
					  ]
			);
			$table->addColumn(
				'joined', 'datetime', [
							'notnull' => false,
						]
			);
			$table->setPrimaryKey(['circle_id', 'group_id']);
		}


		if (!$schema->hasTable('circle_gsevents')) {
			$table = $schema->createTable('circle_gsevents');
			$table->addColumn(
				'token', 'string', [
						   'notnull' => false,
						   'length'  => 63,
					   ]
			);
			$table->addColumn(
				'event', 'text', [
						   'notnull' => false
					   ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'length'  => 255,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'severity', 'integer', [
							  'length'  => 3,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'status', 'integer', [
							'length'  => 3,
							'notnull' => false
						]
			);
			$table->addColumn(
				'creation', 'bigint', [
							  'length'  => 14,
							  'notnull' => false
						  ]
			);
			$table->addUniqueIndex(['token', 'instance']);
		}


		if (!$schema->hasTable('circle_gsshares')) {
			$table = $schema->createTable('circle_gsshares');
			$table->addColumn(
				'id', 'integer', [
						'notnull'       => false,
						'length'        => 11,
						'autoincrement' => true,
						'unsigned'      => true
					]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'length'  => 15,
							   'notnull' => false
						   ]
			);
			$table->addColumn(
				'owner', 'string', [
						   'length'  => 15,
						   'notnull' => false
					   ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'length'  => 255,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'token', 'string', [
						   'notnull' => false,
						   'length'  => 63
					   ]
			);
			$table->addColumn(
				'parent', 'integer', [
							'notnull' => false,
							'length'  => 11,
						]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'length'  => 64,
									 'notnull' => false
								 ]
			);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['circle_id', 'mountpoint_hash']);
		}


		if (!$schema->hasTable('circle_gsshares_mp')) {
			$table = $schema->createTable('circle_gsshares_mp');
			$table->addColumn(
				'share_id', 'integer', [
							  'length'  => 11,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'user_id', 'string', [
							 'length'  => 127,
							 'notnull' => false
						 ]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'length'  => 64,
									 'notnull' => false
								 ]
			);
			$table->setPrimaryKey(['share_id', 'user_id']);
			$table->addUniqueIndex(['share_id', 'mountpoint_hash']);
		}


		if (!$schema->hasTable('circle_links')) {
			$table = $schema->createTable('circle_links');
			$table->addColumn(
				'id', 'smallint', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 3,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'status', 'smallint', [
							'notnull' => true,
							'length'  => 1,
						]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 64,
						   ]
			);
			$table->addColumn(
				'unique_id', 'string', [
							   'notnull' => false,
							   'length'  => 64,
						   ]
			);
			$table->addColumn(
				'address', 'string', [
							 'notnull' => true,
							 'length'  => 128,
						 ]
			);
			$table->addColumn(
				'token', 'string', [
						   'notnull' => true,
						   'length'  => 64,
					   ]
			);
			$table->addColumn(
				'key', 'string', [
						 'notnull' => true,
						 'length'  => 64,
					 ]
			);
			$table->addColumn(
				'creation', 'datetime', [
							  'notnull' => false,
						  ]
			);
			$table->setPrimaryKey(['id']);
		}


		if (!$schema->hasTable('circle_members')) {
			$table = $schema->createTable('circle_members');
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'member_id', Type::STRING, [
							   'notnull' => false,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'contact_id', 'string', [
								'notnull' => false,
								'length'  => 127,
							]
			);
			$table->addColumn(
				'user_type', 'smallint', [
							   'notnull' => true,
							   'length'  => 1,
							   'default' => 1,
						   ]
			);
			$table->addColumn(
				'user_id', 'string', [
							 'notnull' => true,
							 'length'  => 127,
						 ]
			);
			$table->addColumn(
				'cached_name', 'string', [
							 'notnull' => false,
							 'length'  => 255,
							 'default' => ''
						 ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'default' => '',
							  'length'  => 255
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
				'note', 'text', [
						  'notnull' => false
					  ]
			);
			$table->addColumn(
				'joined', 'datetime', [
							'notnull' => false,
						]
			);
			$table->addColumn(
				'contact_meta', 'text', [
								  'notnull' => false
							  ]
			);
			$table->addColumn(
				'contact_checked', Type::SMALLINT, [
									 'notnull' => false,
									 'length'  => 1,
								 ]
			);

			$table->setPrimaryKey(['circle_id', 'user_id', 'user_type', 'contact_id', 'instance']);
		}


		if (!$schema->hasTable('circle_shares')) {
			$table = $schema->createTable('circle_shares');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 4,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'unique_id', 'string', [
							   'notnull' => false,
							   'length'  => 32,
						   ]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'source', 'string', [
							'notnull' => true,
							'length'  => 15,
						]
			);
			$table->addColumn(
				'type', 'string', [
						  'notnull' => true,
						  'length'  => 15,
					  ]
			);
			$table->addColumn(
				'author', 'string', [
							'notnull' => true,
							'length'  => 127,
						]
			);
			$table->addColumn(
				'cloud_id', 'string', [
							  'notnull' => false,
							  'length'  => 254,
							  'default' => 'null',
						  ]
			);
			$table->addColumn(
				'headers', 'text', [
							 'notnull' => false
						 ]
			);
			$table->addColumn(
				'payload', 'text', [
							 'notnull' => true
						 ]
			);
			$table->addColumn(
				'creation', 'datetime', [
							  'notnull' => false,
						  ]
			);
			$table->setPrimaryKey(['id']);
		}


		if (!$schema->hasTable('circle_tokens')) {
			$table = $schema->createTable('circle_tokens');
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'member_id', Type::STRING, [
							   'notnull' => false,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'user_id', 'string', [
							 'notnull' => true,
							 'length'  => 255,
						 ]
			);
			$table->addColumn(
				'share_id', 'bigint', [
							  'notnull' => true,
							  'length'  => 14,
						  ]
			);
			$table->addColumn(
				'token', 'string', [
						   'notnull' => true,
						   'length'  => 31,
					   ]
			);
			$table->addColumn(
				'password', 'string', [
							  'notnull' => true,
							  'length'  => 127,
						  ]
			);
			$table->addColumn(
				'accepted', Type::SMALLINT, [
							  'notnull' => false,
							  'length'  => 1,
						  ]
			);
			$table->setPrimaryKey(['circle_id', 'user_id', 'share_id']);
		}


		return $schema;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('circles_tokens')) {
			$this->copyTable('circles_tokens', 'circle_tokens');
		}
		if ($schema->hasTable('circles_circles')) {
			$this->copyTableCircles('circles_circles', 'circle_circles');
		}
		if ($schema->hasTable('circles_clouds')) {
			$this->copyTable('circles_clouds', 'circle_clouds');
		}
		if ($schema->hasTable('circles_groups')) {
			$this->copyTable('circles_groups', 'circle_groups');
		}
		if ($schema->hasTable('circles_gsevents')) {
			$this->copyTable('circles_gsevents', 'circle_gsevents');
		}
		if ($schema->hasTable('circles_gsshares')) {
			$this->copyTable('circles_gsshares', 'circle_gsshares');
		}
		if ($schema->hasTable('circles_gsshares_mp')) {
			$this->copyTable('circles_gsshares_mp', 'circle_gsshares_mp');
		}
		if ($schema->hasTable('circles_links')) {
			$this->copyTable('circles_links', 'circle_links');
		}
		if ($schema->hasTable('circles_members')) {
			$this->copyTable('circles_members', 'circle_members', ['instance' => '', 'contact_id' => '']);
		}
		if ($schema->hasTable('circles_shares')) {
			$this->copyTable('circles_shares', 'circle_shares');
		}

		$this->updateMemberId();
		$this->updateTokens();
	}


	/**
	 * @param $orig
	 * @param $dest
	 * @param array $default
	 */
	protected function copyTable($orig, $dest, array $default = []) {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('*')
		   ->from($orig);

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$copy = $this->connection->getQueryBuilder();
			$copy->insert($dest);
			$ak = array_keys($row);
			foreach ($ak as $k) {
				if ($row[$k] !== null) {
					$copy->setValue($k, $copy->createNamedParameter($row[$k]));
				} elseif (array_key_exists($k, $default)) {
					$copy->setValue($k, $copy->createNamedParameter($default[$k]));
				}
			}

			$ak = array_keys($default);
			foreach ($ak as $k) {
				if (!array_key_exists($k, $row)) {
					$copy->setValue($k, $copy->createNamedParameter($default[$k]));
				}
			}

			$copy->execute();
		}
	}


	/**
	 * @param $orig
	 * @param $dest
	 */
	protected function copyTableCircles($orig, $dest) {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('*')
		   ->from($orig);

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$copy = $this->connection->getQueryBuilder();
			$copy->insert($dest);
			$ak = array_keys($row);
			foreach ($ak as $k) {
				$v = $row[$k];
				if ($k === 'unique_id') {
					$copy->setValue('unique_id', $copy->createNamedParameter(substr($v, 0, 14)));
					$copy->setValue('long_id', $copy->createNamedParameter($v));
				} else if ($v !== null) {
					$copy->setValue($k, $copy->createNamedParameter($v));
				}
			}

			$copy->execute();
		}
	}


	/**
	 *
	 */
	private function updateMemberId() {
		$qb = $this->connection->getQueryBuilder();
		$expr = $qb->expr();

		$orX = $expr->orX();
		$orX->add($expr->eq('member_id', $qb->createNamedParameter('')));
		$orX->add($expr->isNull('member_id'));

		$qb->select('circle_id', 'user_id', 'user_type', 'instance')
		   ->from('circle_members')
		   ->where($orX);

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$uniqueId = substr(bin2hex(openssl_random_pseudo_bytes(24)), 0, 15);

			$update = $this->connection->getQueryBuilder();
			$expru = $update->expr();
			$update->update('circle_members')
				   ->set('member_id', $update->createNamedParameter($uniqueId))
				   ->where($expru->eq('circle_id', $update->createNamedParameter($row['circle_id'])))
				   ->andWhere($expru->eq('user_id', $update->createNamedParameter($row['user_id'])))
				   ->andWhere($expru->eq('user_type', $update->createNamedParameter($row['user_type'])))
				   ->andWhere($expru->eq('instance', $update->createNamedParameter($row['instance'])));

			$update->execute();
		}
	}


	/**
	 *
	 */
	private function updateTokens() {
		$qb = $this->connection->getQueryBuilder();
		$expr = $qb->expr();
		$orX = $expr->orX();
		$orX->add($expr->eq('member_id', $qb->createNamedParameter('')));
		$orX->add($expr->isNull('member_id'));
		$qb->select('user_id', 'circle_id')
		   ->from('circle_tokens')
		   ->where($orX);

		$result = $qb->execute();
		while ($row = $result->fetch()) {
			$qbm = $this->connection->getQueryBuilder();
			$exprm = $qbm->expr();

			$qbm->select('member_id')
				->from('circle_members')
				->where($exprm->eq('circle_id', $qbm->createNamedParameter($row['circle_id'])))
				->andWhere($exprm->eq('user_id', $qbm->createNamedParameter($row['user_id'])))
				->andWhere($exprm->neq('user_type', $qbm->createNamedParameter('1')));

			$resultm = $qbm->execute();
			$member = $resultm->fetch();

			$update = $this->connection->getQueryBuilder();
			$expru = $update->expr();
			$update->update('circle_tokens')
				   ->set('member_id', $update->createNamedParameter($member['member_id']))
				   ->where($expru->eq('circle_id', $update->createNamedParameter($row['circle_id'])))
				   ->andWhere($expru->eq('user_id', $update->createNamedParameter($row['user_id'])));

			$update->execute();
		}
	}


}
