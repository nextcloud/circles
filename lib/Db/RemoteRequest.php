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
use OCA\Circles\Exceptions\RemoteUidException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class RemoteRequest
 *
 * @package OCA\Circles\Db
 */
class RemoteRequest extends RemoteRequestBuilder {
	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function save(RemoteInstance $remote): void {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteInsertSql();
		$qb->setValue('uid', $qb->createNamedParameter($remote->getUid(true)))
		   ->setValue('instance', $qb->createNamedParameter($remote->getInstance()))
		   ->setValue('href', $qb->createNamedParameter($remote->getId()))
		   ->setValue('type', $qb->createNamedParameter($remote->getType()))
		   ->setValue('interface', $qb->createNamedParameter($remote->getInterface()))
		   ->setValue('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));

		$qb->execute();
	}


	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function update(RemoteInstance $remote) {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteUpdateSql();
		$qb->set('uid', $qb->createNamedParameter($remote->getUid(true)))
		   ->set('href', $qb->createNamedParameter($remote->getId()))
		   ->set('type', $qb->createNamedParameter($remote->getType()))
		   ->set('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));

		$qb->limitToInstance($remote->getInstance());

		$qb->execute();
	}


	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function updateItem(RemoteInstance $remote) {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteUpdateSql();
		$qb->set('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));

		$qb->limit('uid', $remote->getUid(true), '', false);

		$qb->execute();
	}


	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function updateInstance(RemoteInstance $remote) {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteUpdateSql();
		$qb->set('instance', $qb->createNamedParameter($remote->getInstance()));

		$qb->limit('uid', $remote->getUid(true), '', false);

		$qb->execute();
	}


	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function updateType(RemoteInstance $remote) {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteUpdateSql();
		$qb->set('type', $qb->createNamedParameter($remote->getType()));

		$qb->limit('uid', $remote->getUid(true), '', false);

		$qb->execute();
	}


	/**
	 * @param RemoteInstance $remote
	 *
	 * @throws RemoteUidException
	 */
	public function updateHref(RemoteInstance $remote) {
		$remote->mustBeIdentityAuthed();
		$qb = $this->getRemoteUpdateSql();
		$qb->set('href', $qb->createNamedParameter($remote->getId()));

		$qb->limit('uid', $remote->getUid(true), '', false);

		$qb->execute();
	}


	/**
	 * @return RemoteInstance[]
	 */
	public function getAllInstances(): array {
		$qb = $this->getRemoteSelectSql();

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @return RemoteInstance[]
	 */
	public function getKnownInstances(): array {
		$qb = $this->getRemoteSelectSql();
		$qb->filter('type', RemoteInstance::TYPE_UNKNOWN, '', false);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * - returns:
	 * - all GLOBAL_SCALE
	 * - TRUSTED if Circle is Federated
	 * - EXTERNAL if Circle is Federated and a contains a member from instance
	 *
	 * @param Circle $circle
	 * @param bool $broadcastAsFederated
	 *
	 * @return RemoteInstance[]
	 * @throws RequestBuilderException
	 */
	public function getOutgoingRecipient(Circle $circle, bool $broadcastAsFederated = false): array {
		$qb = $this->getRemoteSelectSql();
		$expr = $qb->expr();
		$orX = $expr->orX();

		$orX->add($qb->exprLimit('type', RemoteInstance::TYPE_GLOBALSCALE, '', false));

		if ($circle->isConfig(Circle::CFG_FEDERATED) || $broadcastAsFederated) {
			// get all TRUSTED
			$orX->add($qb->exprLimit('type', RemoteInstance::TYPE_TRUSTED, '', false));

			// get EXTERNAL with Members
			$aliasMember = $qb->generateAlias(CoreQueryBuilder::REMOTE, CoreQueryBuilder::MEMBER);
			$qb->leftJoin(
				CoreQueryBuilder::REMOTE, self::TABLE_MEMBER, $aliasMember,
				$expr->andX(
					$expr->eq($aliasMember . '.circle_id', $qb->createNamedParameter($circle->getSingleId())),
					$expr->eq($aliasMember . '.instance', CoreQueryBuilder::REMOTE . '.instance'),
					$expr->gte(
						$aliasMember . '.level',
						$qb->createNamedParameter(Member::LEVEL_MEMBER, IQueryBuilder::PARAM_INT)
					)
				)
			);

			$external = $expr->andX();
			$external->add($qb->exprLimit('type', RemoteInstance::TYPE_EXTERNAL, '', false));
			$external->add($expr->isNotNull($aliasMember . '.instance'));
			$orX->add($external);
		}

		$qb->andWhere($orX);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $host
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function getFromInstance(string $host): RemoteInstance {
		$qb = $this->getRemoteSelectSql();
		$qb->limitToInstance($host);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $href
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function getFromHref(string $href): RemoteInstance {
		$qb = $this->getRemoteSelectSql();
		$qb->limit('href', $href, '', false);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $status
	 *
	 * @return RemoteInstance[]
	 */
	public function getFromType(string $status): array {
		$qb = $this->getRemoteSelectSql();
		$qb->limitToTypeString($status);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param RemoteInstance $remoteInstance
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function searchDuplicate(RemoteInstance $remoteInstance): RemoteInstance {
		$qb = $this->getRemoteSelectSql();
		$orX = $qb->expr()->orX();
		$orX->add($qb->exprLimit('href', $remoteInstance->getId(), '', false));
		$orX->add($qb->exprLimit('uid', $remoteInstance->getUid(true), '', false));
		$orX->add($qb->exprLimit('instance', $remoteInstance->getInstance(), '', false));
		$qb->andWhere($orX);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param RemoteInstance $remoteInstance
	 */
	public function deleteById(RemoteInstance $remoteInstance) {
		$qb = $this->getRemoteDeleteSql();
		$qb->limitToId($remoteInstance->getDbId());
		$qb->execute();
	}
}
