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


	/**
	 * @param FederatedLink $link
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(FederatedLink $link) {

		if ($link === null) {
			return false;
		}

		try {
			$qb = $this->getLinksInsertSql();
			$qb->setValue('status', $qb->createNamedParameter($link->getStatus()))
			   ->setValue('circle_id', $qb->createNamedParameter($link->getCircleId()))
			   ->setValue('unique_id', $qb->createNamedParameter($link->getUniqueId(true)))
			   ->setValue('address', $qb->createNamedParameter($link->getAddress()))
			   ->setValue('token', $qb->createNamedParameter($link->getToken(true)));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	public function update(FederatedLink $link) {

		if ($link->getStatus() === FederatedLink::STATUS_LINK_REMOVE) {
			$this->delete($link);

			return;
		}

		$qb = $this->getLinksUpdateSql();
		$expr = $qb->expr();
		$qb->set('status', $qb->createNamedParameter($link->getStatus()));
		if ($link->getUniqueId() !== '') {
			$qb->set('unique_id', $qb->createNamedParameter($link->getUniqueId(true)));
		}

		$qb->where(
			$expr->andX(
				$expr->eq('circle_id', $qb->createNamedParameter($link->getCircleId())),
				$expr->eq('token', $qb->createNamedParameter($link->getToken(true)))
			)
		);

		$qb->execute();
	}


	/**
	 * @param int $circleId
	 *
	 * @return FederatedLink[]
	 */
	public function getLinked($circleId) {
		$qb = $this->getLinksSelectSql();
		$expr = $qb->expr();

		$qb->where(
			$expr->andX(
				$expr->eq('f.circle_id', $qb->createNamedParameter((int)$circleId)),
				$expr->eq('f.status', $qb->createNamedParameter(9))
			)
		);

		$result = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$entry = $this->getLinkFromEntry($data);
			$result[] = $entry;
		}
		$cursor->closeCursor();

		return $result;
	}


	/**
	 * @param int $circleId
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 */
	public function getFromUniqueId($circleId, $uniqueId) {
		$qb = $this->getLinksSelectSql();
		$expr = $qb->expr();

		$qb->where(
			$expr->andX(
				$expr->eq('f.circle_id', $qb->createNamedParameter((int)$circleId)),
				$expr->eq('f.unique_id', $qb->createNamedParameter((string)$uniqueId))
			)
		);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		return $this->getLinkFromEntry($data);
	}



	public function delete(FederatedLink $link) {

		if ($link === null) {
			return;
		}

		$qb = $this->getLinksDeleteSql();
		$expr = $qb->expr();

		$qb->where(
			$expr->andX(
				$expr->eq('token', $qb->createNamedParameter($link->getToken(true))),
				$expr->eq('circle_id', $qb->createNamedParameter($link->getCircleId()))
			)
		);

		$qb->execute();
	}


	/**
	 * @param array $data
	 *
	 * @return FederatedLink
	 */
	public function getLinkFromEntry($data) {
		if ($data === false || $data === null) {
			return null;
		}

		$link = new FederatedLink();
		$link->setId($data['id'])
			 ->setUniqueId($data['unique_id'])
			 ->setStatus($data['status'])
			 ->setAddress($data['address'])
			 ->setToken($data['token'])
			 ->setCircleId($data['circle_id']);

		return $link;
	}
}