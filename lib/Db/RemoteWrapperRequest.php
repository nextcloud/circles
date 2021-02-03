<?php

declare(strict_types=1);


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


use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCA\Circles\Model\Federated\RemoteWrapper;


/**
 * Class RemoteWrapperRequest
 *
 * @package OCA\Circles\Db
 */
class RemoteWrapperRequest extends RemoteWrapperRequestBuilder {


	/**
	 * @param RemoteWrapper $wrapper
	 */
	public function create(RemoteWrapper $wrapper): void {
		$qb = $this->getRemoteWrapperInsertSql();
		$qb->setValue('token', $qb->createNamedParameter($wrapper->getToken()))
		   ->setValue('event', $qb->createNamedParameter(json_encode($wrapper->getEvent())))
		   ->setValue('instance', $qb->createNamedParameter($wrapper->getInstance()))
		   ->setValue('severity', $qb->createNamedParameter($wrapper->getSeverity()))
		   ->setValue('status', $qb->createNamedParameter($wrapper->getStatus()))
		   ->setValue('creation', $qb->createNamedParameter($wrapper->getCreation()));

		$qb->execute();
	}

	/**
	 * @param RemoteWrapper $wrapper
	 */
	public function update(RemoteWrapper $wrapper): void {
		$qb = $this->getRemoteWrapperUpdateSql();
		$qb->set('event', $qb->createNamedParameter(json_encode($wrapper->getEvent())))
		   ->set('status', $qb->createNamedParameter($wrapper->getStatus()));

		$qb->limitToInstance($wrapper->getInstance());
		$qb->limitToToken($wrapper->getToken());

		$qb->execute();
	}


	/**
	 * @param string $token
	 *
	 * @return RemoteWrapper[]
	 */
	public function getByToken(string $token): array {
		$qb = $this->getRemoteWrapperSelectSql();
		$qb->limitToToken($token);

		return $this->getItemsFromRequest($qb);
	}

}

