<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Model\AppService;


/**
 * Class RemoteRequest
 *
 * @package OCA\Circles\Db
 */
class RemoteRequest extends RemoteRequestBuilder {


	/**
	 * @param AppService $remote
	 */
	public function save(AppService $remote): void {
		$qb = $this->getRemoteInsertSql();
		$qb->setValue('uid', $qb->createNamedParameter($remote->getUid(true)))
		   ->setValue('instance', $qb->createNamedParameter($remote->getInstance()))
		   ->setValue('href', $qb->createNamedParameter($remote->getId()))
		   ->setValue('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));

		$qb->execute();
	}


	/**
	 * @param AppService $remote
	 */
	public function update(AppService $remote) {
		$qb = $this->getRemoteUpdateSql();
		$qb->set('uid', $qb->createNamedParameter($remote->getUid(true)))
		   ->set('href', $qb->createNamedParameter($remote->getId()))
		   ->set('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));

		$qb->limitToInstance($remote->getInstance());

		$qb->execute();
	}


	/**
	 * @param AppService $remote
	 */
	public function updateInstance(AppService $remote) {
		$qb = $this->getRemoteUpdateSql();
		$qb->set('instance', $qb->createNamedParameter($remote->getInstance()));

		$qb->limitToDBField('uid', $remote->getUid(true), false);

		$qb->execute();
	}


	/**
	 * @param AppService $remote
	 */
	public function updateHref(AppService $remote) {
		$qb = $this->getRemoteUpdateSql();
		$qb->set('href', $qb->createNamedParameter($remote->getId()));

		$qb->limitToDBField('uid', $remote->getUid(true), false);

		$qb->execute();
	}


	/**
	 * @param string $host
	 *
	 * @return AppService
	 * @throws RemoteNotFoundException
	 */
	public function getFromInstance(string $host): AppService {
		$qb = $this->getRemoteSelectSql();
		$qb->limitToInstance($host);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $href
	 *
	 * @return AppService
	 * @throws RemoteNotFoundException
	 */
	public function getFromHref(string $href): AppService {
		$qb = $this->getRemoteSelectSql();
		$qb->limitToDBField('href', $href, false);

		return $this->getItemFromRequest($qb);
	}


}

