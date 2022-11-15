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

use OCA\Circles\Exceptions\FederatedLinkDoesNotExistException;
use OCA\Circles\Model\FederatedLink;

/**
 * @deprecated
 * Class FederatedLinksRequest
 *
 * @package OCA\Circles\Db
 */
class FederatedLinksRequest extends FederatedLinksRequestBuilder {
	/**
	 * @param FederatedLink $link
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(FederatedLink $link) {
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


	/**
	 * @param FederatedLink $link
	 */
	public function update(FederatedLink $link) {
		if ($link->getStatus() === FederatedLink::STATUS_LINK_REMOVE) {
			$this->delete($link);

			return;
		}

		$qb = $this->getLinksUpdateSql();
		$qb->set('status', $qb->createNamedParameter($link->getStatus()));
		if ($link->getUniqueId() !== '') {
			$qb->set('unique_id', $qb->createNamedParameter($link->getUniqueId(true)));
		}

		$this->limitToToken($qb, $link->getToken(true));
		$this->limitToCircleId($qb, $link->getCircleId());

		$qb->execute();
	}


	/**
	 * @param FederatedLink $link
	 */
	public function delete(FederatedLink $link) {
		if ($link === null) {
			return;
		}

		$qb = $this->getLinksDeleteSql();
		$this->limitToToken($qb, $link->getToken(true));
		$this->limitToCircleId($qb, $link->getCircleId());

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


	/**
	 * returns all FederatedLink from a circle
	 *
	 * @param string $circleUniqueId
	 * @param int $status
	 *
	 * @return FederatedLink[]
	 */
	public function getLinksFromCircle($circleUniqueId, $status = 0) {
		$qb = $this->getLinksSelectSql();
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToStatus($qb, $status);

		$links = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$links[] = $this->parseLinksSelectSql($data);
		}
		$cursor->closeCursor();

		return $links;
	}


	/**
	 * returns a FederatedLink from a circle identified by its full unique Id
	 *
	 * @param string $circleUniqueId
	 * @param string $linkUniqueId
	 *
	 * @return FederatedLink
	 * @throws FederatedLinkDoesNotExistException
	 */
	public function getLinkFromCircle($circleUniqueId, $linkUniqueId) {
		$qb = $this->getLinksSelectSql();
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToUniqueId($qb, $linkUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new FederatedLinkDoesNotExistException($this->l10n->t('Federated link not found'));
		}

		return $this->parseLinksSelectSql($data);
	}


	/**
	 * return the FederatedLink identified by a remote Circle UniqueId and the Token of the link
	 *
	 * @param string $token
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 * @throws FederatedLinkDoesNotExistException
	 */
	public function getLinkFromToken($token, $uniqueId) {
		$qb = $this->getLinksSelectSql();
		$this->limitToUniqueId($qb, (string)$uniqueId);
		$this->limitToToken($qb, (string)$token);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new FederatedLinkDoesNotExistException($this->l10n->t('Federated link not found'));
		}

		return $this->parseLinksSelectSql($data);
	}


	/**
	 * return the FederatedLink identified by a its Id
	 *
	 * @param string $linkUniqueId
	 *
	 * @return FederatedLink
	 * @throws FederatedLinkDoesNotExistException
	 */
	public function getLinkFromId($linkUniqueId) {
		$qb = $this->getLinksSelectSql();
		$this->limitToUniqueId($qb, $linkUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new FederatedLinkDoesNotExistException($this->l10n->t('Federated link not found'));
		}

		return $this->parseLinksSelectSql($data);
	}
}
