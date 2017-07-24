<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
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

use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Model\Circle;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class UpdateShareTimeToTimestamp
 *
 * @package OCA\Circles\Migration
 */
class LinkUsingShortenUniqueIdInsteadOfCircleId implements IRepairStep {

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
		return 'Using shorten unique id instead of circle id';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$oldVersion = explode(
			'.', \OC::$server->getConfig()
							 ->getAppValue('circles', 'installed_version', '')
		);

		if ((int)$oldVersion[0] === 0 && $oldVersion[1] < 12) {
			$this->swapToShortenUniqueId($output);
		}
	}


	private function swapToShortenUniqueId(IOutput $output) {

		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'unique_id')
		   ->from(CoreRequestBuilder::TABLE_CIRCLES);

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$circleId = $data['id'];
			$shortenUniqueId = substr($data['unique_id'], 0, Circle::UNIQUEID_SHORT_LENGTH);

			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, CoreRequestBuilder::TABLE_GROUPS
			);
			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, CoreRequestBuilder::TABLE_LINKS
			);
			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, CoreRequestBuilder::TABLE_MEMBERS
			);
			$this->swapToShortenUniqueIdInTable(
				$circleId, $shortenUniqueId, CoreRequestBuilder::TABLE_LINKS
			);
		}
		$cursor->closeCursor();
	}


	private function swapToShortenUniqueIdInTable($circleId, $shortenUniqueId, $table) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update($table)
		   ->where(
			   $qb->expr()
				  ->eq('circle_id', $qb->createNamedParameter($circleId))
		   );

		$qb->set('circle_id', $qb->createNamedParameter($shortenUniqueId));
		$qb->execute();
	}

}


