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

use OCA\Circles\Tools\Traits\TStringTools;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCA\Circles\Model\DeprecatedMember;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * @deprecated
 * Class GSSharesRequest
 *
 * @package OCA\Circles\Db
 */
class GSSharesRequest extends GSSharesRequestBuilder {
	use TStringTools;


	/**
	 * @param GSShare $gsShare
	 */
	public function create(GSShare $gsShare): void {
		$hash = $this->token();
		$qb = $this->getGSSharesInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($gsShare->getCircleId()))
		   ->setValue('owner', $qb->createNamedParameter($gsShare->getOwner()))
		   ->setValue('instance', $qb->createNamedParameter($gsShare->getInstance()))
		   ->setValue('token', $qb->createNamedParameter($gsShare->getToken()))
		   ->setValue('parent', $qb->createNamedParameter($gsShare->getParent()))
		   ->setValue('mountpoint', $qb->createNamedParameter($gsShare->getMountPoint()))
		   ->setValue('mountpoint_hash', $qb->createNamedParameter($hash));
		$qb->execute();
	}


	/**
	 * @param string $userId
	 *
	 * @return GSShare[]
	 */
	public function getForUser(string $userId): array {
		$qb = $this->getGSSharesSelectSql();

		$this->joinMembership($qb, $userId);
		$this->leftJoinMountPoint($qb, $userId);

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $this->parseGSSharesSelectSql($data);
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * @param DeprecatedMember $member
	 */
	public function removeGSSharesFromMember(DeprecatedMember $member) {
		$qb = $this->getGSSharesDeleteSql();
		$this->limitToCircleId($qb, $member->getCircleId());
		$this->limitToInstance($qb, $member->getInstance());
		$this->limitToOwner($qb, $member->getUserId());

		$qb->execute();
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	private function joinMembership(IQueryBuilder $qb, string $userId) {
		$qb->from(DeprecatedRequestBuilder::TABLE_MEMBERS, 'm');

		$expr = $qb->expr();
		$andX = $expr->andX();

		$andX->add($expr->eq('m.user_id', $qb->createNamedParameter($userId)));
		$andX->add($expr->eq('m.instance', $qb->createNamedParameter('')));
		$andX->add($expr->gt('m.level', $qb->createNamedParameter(0)));
		$andX->add($expr->eq('m.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER)));
		$andX->add($expr->eq('m.circle_id', 'gsh.circle_id'));

		$qb->andWhere($andX);
	}


	private function leftJoinMountPoint(IQueryBuilder $qb, string $userId) {
		$expr = $qb->expr();
		$pf = '' . $this->default_select_alias . '.';

		$on = $expr->andX();
		$on->add($expr->eq('mp.user_id', $qb->createNamedParameter($userId)));
		$on->add($expr->eq('mp.share_id', $pf . 'id'));

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->selectAlias('mp.mountPoint', 'gsshares_mountpoint')
		   ->leftJoin($this->default_select_alias, DeprecatedRequestBuilder::TABLE_GSSHARES_MOUNTPOINT, 'mp', $on);
	}


	/**
	 * @param string $userId
	 * @param string $target
	 *
	 * @return GSShareMountpoint
	 * @throws ShareNotFound
	 */
	public function getShareMountPointByPath(string $userId, string $target): GSShareMountpoint {
		$qb = $this->getGSSharesMountpointSelectSql();

		$targetHash = md5($target);
		$this->limitToUserId($qb, $userId);
		$this->limitToMountpointHash($qb, $targetHash);

		$shares = [];
		$cursor = $qb->execute();
		$data = $cursor->fetch();

		if ($data === false) {
			throw new ShareNotFound();
		}

		return $this->parseGSSharesMountpointSelectSql($data);
	}


	/**
	 * @param int $gsShareId
	 * @param string $userId
	 *
	 * @return GSShareMountpoint
	 * @throws ShareNotFound
	 */
	public function getShareMountPointById(int $gsShareId, string $userId): GSShareMountpoint {
		$qb = $this->getGSSharesMountpointSelectSql();

		$this->limitToShareId($qb, $gsShareId);
		$this->limitToUserId($qb, $userId);

		$shares = [];
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		if ($data === false) {
			throw new ShareNotFound();
		}

		return $this->parseGSSharesMountpointSelectSql($data);
	}


	/**
	 * @param GSShareMountpoint $mountpoint
	 */
	public function generateShareMountPoint(GSShareMountpoint $mountpoint) {
		$qb = $this->getGSSharesMountpointInsertSql();

		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());

		$qb->setValue('user_id', $qb->createNamedParameter($mountpoint->getUserId()))
		   ->setValue('share_id', $qb->createNamedParameter($mountpoint->getShareId()))
		   ->setValue('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
		   ->setValue('mountpoint_hash', $qb->createNamedParameter($hash));
		$qb->execute();
	}


	/**
	 * @param GSShareMountpoint $mountpoint
	 *
	 * @return bool
	 */
	public function updateShareMountPoint(GSShareMountpoint $mountpoint) {
		$qb = $this->getGSSharesMountpointUpdateSql();

		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());

		$qb->set('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
		   ->set('mountpoint_hash', $qb->createNamedParameter($hash));

		$this->limitToShareId($qb, $mountpoint->getShareId());
		$this->limitToUserId($qb, $mountpoint->getUserId());
		$nb = $qb->execute();

		return ($nb === 1);
	}
}
