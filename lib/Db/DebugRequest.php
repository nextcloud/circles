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


namespace OCA\Circles\Db;

use OCA\Circles\Model\Debug;

/**
 * Class ShareRequest
 *
 * @package OCA\Circles\Db
 */
class DebugRequest extends DebugRequestBuilder {


	/**
	 * @param Debug $debug
	 */
	public function save(Debug $debug): void {
		$qb = $this->getDebugInsertSql();
		$qb->setValue('thread', $qb->createNamedParameter($debug->getThread()))
		   ->setValue('type', $qb->createNamedParameter($debug->getType()))
		   ->setValue('circle_id', $qb->createNamedParameter($debug->getCircleId()))
		   ->setValue('instance', $qb->createNamedParameter($debug->getInstance()))
		   ->setValue('debug', $qb->createNamedParameter(json_encode($debug->getDebug())))
		   ->setValue('time', $qb->createNamedParameter($debug->getTime()));

		$qb->execute();
	}


	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function since(int $id): array {
		$qb = $this->getDebugSelectSql();
		$qb->gt('id', $id);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param int $history
	 *
	 * @return Debug[]
	 */
	public function getHistory(int $history): array {
		$qb = $this->getDebugSelectSql();

		$qb->orderBy('id', 'desc');
		$qb->paginate($history);

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @param int $lastId
	 *
	 * @return Debug[]
	 */
	public function getSince(int $lastId): array {
		$qb = $this->getDebugSelectSql();

		$qb->orderBy('id', 'desc');
		$qb->gt('id', $lastId);

		return $this->getItemsFromRequest($qb);
	}
}
