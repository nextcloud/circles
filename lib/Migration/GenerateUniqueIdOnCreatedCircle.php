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
use OCP\Share;

/**
 * Class UpdateShareTimeToTimestamp
 *
 * @package OCA\Circles\Migration
 */
class GenerateUniqueIdOnCreatedCircle implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var  IConfig */
	protected $config;

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
		return 'Generate unique id on created circle';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$this->generateUniqueId($output);
	}

	/**
	 * Update shares
	 * - type 7 instead of 1
	 * - with circle ID instead of `customgroup_` + group URI
	 *
	 * @param IOutput $output
	 */
	public function generateUniqueId(IOutput $output) {
		$output->info('Generate unique id on circles');

		$qb = $this->connection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->select('id')
		   ->from('circles_circles')
		   ->where($expr->eq('unique_id', $qb->createNamedParameter('')));

		$result = $qb->execute();

		$output->startProgress();
		while ($row = $result->fetch()) {

			$itemId = $row['id'];
			$uniqueId = bin2hex(openssl_random_pseudo_bytes(24));

			$update = $this->connection->getQueryBuilder();
			$update->update('circles_circles')
				   ->set('unique_id', $update->createNamedParameter('d' . $uniqueId))
				   ->where(
					   $update->expr()
							  ->eq('id', $update->createNamedParameter($itemId))
				   );
			$update->execute();

			$output->advance();

		}

		$result->closeCursor();
		$output->finishProgress();
	}

}
