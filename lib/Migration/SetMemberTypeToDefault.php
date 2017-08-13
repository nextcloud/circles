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
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class UsingShortenUniqueIdInsteadOfCircleId
 *
 * @package OCA\Circles\Migration
 */
class SetMemberTypeToDefault implements IRepairStep {

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
		$this->setMemberTypeToDefault();
	}


	private function setMemberTypeToDefault() {

		$qb = $this->connection->getQueryBuilder();
		$qb->update(CoreRequestBuilder::TABLE_MEMBERS)
		   ->where(
			   $qb->expr()
				  ->eq('user_type', $qb->createNamedParameter(0))
		   );

		$qb->set('user_type', $qb->createNamedParameter(1));
		$qb->execute();
	}

}


