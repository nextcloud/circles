<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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


namespace OCA\Circles\Db;


use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;
use OCA\Circles\Model\GlobalScale\GSWrapper;


/**
 * Class GSEventsRequest
 *
 * @package OCA\Circles\Db
 */
class GSEventsRequest extends GSEventsRequestBuilder {


	/**
	 * @param GSWrapper $wrapper
	 *
	 * @return GSWrapper
	 */
	public function create(GSWrapper $wrapper): GSWrapper {
		$qb = $this->getGSEventsInsertSql();
		$qb->setValue('token', $qb->createNamedParameter($wrapper->getToken()))
		   ->setValue('event', $qb->createNamedParameter(json_encode($wrapper->getEvent())))
		   ->setValue('instance', $qb->createNamedParameter($wrapper->getInstance()))
		   ->setValue('severity', $qb->createNamedParameter($wrapper->getSeverity()))
		   ->setValue('status', $qb->createNamedParameter($wrapper->getStatus()))
		   ->setValue('creation', $qb->createNamedParameter($wrapper->getCreation()));

		$qb->execute();

		return $wrapper;
	}

	/**
	 * @param GSWrapper $wrapper
	 */
	public function update(GSWrapper $wrapper): void {
		$qb = $this->getGSEventsUpdateSql();
		$qb->set('event', $qb->createNamedParameter(json_encode($wrapper->getEvent())))
		   ->set('status', $qb->createNamedParameter($wrapper->getStatus()));

		$this->limitToInstance($qb, $wrapper->getInstance());
		$this->limitToToken($qb, $wrapper->getToken());

		$qb->execute();
	}


	/**
	 * @param string $token
	 *
	 * @return GSWrapper[]
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function getByToken(string $token): array {
		$qb = $this->getGSEventsSelectSql();
		$this->limitToToken($qb, $token);

		$wrappers = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$wrappers[] = $this->parseGSEventsSelectSql($data);
		}
		$cursor->closeCursor();

		return $wrappers;
	}

}

