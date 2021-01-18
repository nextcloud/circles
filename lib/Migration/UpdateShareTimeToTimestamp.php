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

use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\Member;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Share;

/**
 * Class UpdateShareTimeToTimestamp
 *
 * @package OCA\Circles\Migration
 */
class UpdateShareTimeToTimestamp implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var  IConfig */
	protected $config;

	/** @var array */
	protected $circlesById = [];
	/** @var array */
	protected $circlesByUri = [];
	/** @var array */
	protected $circleHasAdmin = [];

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
		return 'Fix the shares to use timestamp instead of datetime';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$this->updateShares($output);
	}

	/**
	 * Update shares
	 * - type 7 instead of 1
	 * - with circle ID instead of `customgroup_` + group URI
	 *
	 * @param IOutput $output
	 */
	public function updateShares(IOutput $output) {
		$output->info('Update timestamp of shares');

		$select = $this->connection->getQueryBuilder();
		$select->select('*')
			->from('share')
			->where($select->expr()->eq('share_type', $select->createNamedParameter(7)));

		$update = $this->connection->getQueryBuilder();
		$update->update('share')
			->set('stime', $update->createParameter('time'))
			->where($update->expr()->eq('id', $update->createParameter('id')));

		$output->startProgress();
		$result = $select->execute();

		while ($row = $result->fetch()) {
			$dateTime = \DateTime::createFromFormat('YmdHis', $row['stime']);

			if ($dateTime === false) {
				// Not the invalid format
				continue;
			}

			$update->setParameter('time', $dateTime->getTimestamp())
				->setParameter('id', $row['id']);

			$update->execute();
			$output->advance();
		}

		$result->closeCursor();
		$output->finishProgress();
	}

}
