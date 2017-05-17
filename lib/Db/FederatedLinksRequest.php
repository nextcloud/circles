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


namespace OCA\Circles\Db;


use OCA\Circles\Model\FederatedLink;

class FederatedLinksRequest extends FederatedLinksRequestBuilder {


	public function create(FederatedLink $link) {
		try {
			$qb = $this->getLinksInsertSql();
			$qb->setValue('status', $qb->createNamedParameter($link->getStatus()))
			   ->setValue('circle_id', $qb->createNamedParameter($link->getCircleId()))
			   ->setValue('remote_circle_id', $qb->createNamedParameter($link->getRemoteCircleId()))
			   ->setValue('address', $qb->createNamedParameter($link->getAddress()))
			   ->setValue('token', $qb->createNamedParameter($link->getToken()));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	public function update(FederatedLink $link) {
		$qb = $this->getLinksUpdateSql();
		$expr = $qb->expr();

		$qb->set('status', $qb->createNamedParameter($link->getStatus()));
		if ($link->getRemoteCircleId() > 0) {
			$qb->set('remote_circle_id', $qb->createNamedParameter($link->getRemoteCircleId()));
		}

		$qb->where(
			$expr->andX(
				$expr->eq('circle_id', $qb->createNamedParameter($link->getCircleId())),
				$expr->eq('token', $qb->createNamedParameter($link->getToken()))
			)
		);

		$qb->execute();
	}


	public function delete(FederatedLink $link) {

		$this->miscService->log("DELETRE !! " . var_export($link, true));
		if ($link === null) {
			return;
		}

		$qb = $this->getLinksDeleteSql();
		$expr = $qb->expr();

		$qb->where(
			$expr->andX(
				$expr->eq('token', $qb->createNamedParameter($link->getToken())),
				$expr->eq('circle_id', $qb->createNamedParameter($link->getCircleId()))
			)
		);

		$qb->execute();
	}

}