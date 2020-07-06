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
use daita\MySmallPhpTools\Traits\TStringTools;
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version0019Date20200702124412 extends SimpleMigrationStep {


	/** @var IDBConnection */
	private $connection;

	/** @var TimezoneService */
	private $timezoneService;


	/**
	 * @param IDBConnection $connection
	 * @param TimezoneService $timezoneService
	 */
	public function __construct(IDBConnection $connection, TimezoneService $timezoneService) {
		$this->connection = $connection;
		$this->timezoneService = $timezoneService;
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

		$this->migrateGroups($schema);
	}


	use TStringTools;


	/**
	 * @param ISchemaWrapper $schema
	 */
	private function migrateGroups(ISchemaWrapper $schema) {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('*')
		   ->from('circle_groups');

		$result = $qb->execute();
		while ($row = $result->fetch()) {

			$current = $this->connection->getQueryBuilder();
			$current->select('*');
			$current->from('circle_members');
			$expr = $current->expr();
			$andX = $expr->andX();
			$andX->add($expr->eq('circle_id', $current->createNamedParameter($row['circle_id'])));
			$andX->add($expr->eq('user_id', $current->createNamedParameter($row['group_id'])));
			$andX->add($expr->eq('user_type', $current->createNamedParameter(2)));
			$andX->add($expr->eq('instance', $current->createNamedParameter('')));
			$current->where($andX);

			$cursor = $current->execute();
			$data = $cursor->fetch();
			$cursor->closeCursor();
			if ($data !== false) {
				continue;
			}

			$copy = $this->connection->getQueryBuilder();
			$memberId = $this->token(14);

			$copy->insert('circle_members')
				 ->setValue('circle_id', $copy->createNamedParameter($row['circle_id']))
				 ->setValue('user_id', $copy->createNamedParameter($row['group_id']))
				 ->setValue('level', $copy->createNamedParameter($row['level']))
				 ->setValue('member_id', $copy->createNamedParameter($memberId))
				 ->setValue('user_type', $copy->createNamedParameter(2))
				 ->setValue('status', $copy->createNamedParameter('Member'))
				 ->setValue('instance', $copy->createNamedParameter(''))
				 ->setValue('contact_id', $copy->createNamedParameter(''))
				 ->setValue('joined', $copy->createNamedParameter($this->timezoneService->getUTCDate()));

			$copy->execute();
		}

	}


}
