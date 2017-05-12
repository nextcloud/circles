<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
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

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class ImportOwncloudCustomGroups
 *
 * @package OCA\Circles\Migration
 */
class ImportOwncloudCustomGroups implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var  IConfig */
	protected $config;

	/** @var array */
	protected $circles = [];

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Fix the share type of guest shares when migrating from ownCloud';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if (!$this->shouldRun()) {
			return;
		}

		$this->createCircles($output);
		$this->createMemberships($output);

		$this->config->setAppValue('circles', 'imported_custom_groups', 'true');
	}

	/**
	 * @param IOutput $output
	 */
	public function createCircles(IOutput $output) {
		$output->info('Creating circles');

		$select = $this->connection->getQueryBuilder();
		$select->select('*')
			->from('custom_group')
			->orderBy('group_id');

		$insert = $this->connection->getQueryBuilder();
		$insert->insert('circles_circles')
			->values([
				'name' => $insert->createParameter('name'),
				'type' => $insert->createParameter('type'),
				'creation' => $insert->createFunction('NOW()'),
			]);

		$output->startProgress();
		$result = $select->execute();

		while ($row = $result->fetch()) {
			$insert->setParameter('name', $row['display_name'])
				->setParameter('type', Circle::CIRCLES_PERSONAL);

			$insert->execute();
			$output->advance();

			$this->circles[$row['groud_id']] = $insert->getLastInsertId();
		}

		$result->closeCursor();
		$output->finishProgress();
	}

	/**
	 * @param IOutput $output
	 */
	public function createMemberships(IOutput $output) {
		$output->info('Creating memberships');

		$select = $this->connection->getQueryBuilder();
		$select->select('*')
			->from('custom_group_member')
			->orderBy('group_id');

		$insert = $this->connection->getQueryBuilder();
		$insert->insert('circles_members')
			->values([
				'circle_id' => $insert->createParameter('circle_id'),
				'user_id' => $insert->createParameter('user_id'),
				'level' => $insert->createParameter('level'),
				'status' => $insert->createParameter('status'),
				'joined' => $insert->createFunction('NOW()'),
			]);

		$output->startProgress();
		$result = $select->execute();

		while ($row = $result->fetch()) {
			if (!isset($this->circles[$row['group_id']])) {
				// Stray membership
				continue;
			}

			$insert->setParameter('circle_id', $this->circles[$row['group_id']])
				->setParameter('user_id', $row['user_id'])
				->setParameter('level', (int) $row['role'] === 1 ? Member::LEVEL_OWNER : Member::LEVEL_MEMBER)
				->setParameter('status', 'Member');

			$insert->execute();
			$output->advance();
		}

		$result->closeCursor();
		$output->finishProgress();
	}

	protected function shouldRun() {
		$alreadyImported = $this->config->getAppValue('circles', 'imported_custom_groups', 'false');
		return !$alreadyImported && $this->connection->tableExists('custom_group') && $this->connection->tableExists('custom_group_member');
	}

}
