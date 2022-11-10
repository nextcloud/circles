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

use OCA\Circles\MountManager\Model\RemoteMount;
use OCA\Circles\Tools\Traits\TStringTools;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;

/**
 * Class MountRequest
 *
 * @package OCA\Circles\Db
 */
class MountRequest extends MountRequestBuilder {
	use TStringTools;

	/**
	 * @param RemoteMount $mount
	 */
	public function save(RemoteMount $mount): void {
		$qb = $this->getMountInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($mount->getCircleId()))
		   ->setValue('mount_id', $qb->createNamedParameter($mount->getMountId()))
		   ->setValue('single_id', $qb->createNamedParameter($mount->getOwner()->getSingleId()))
		   ->setValue('token', $qb->createNamedParameter($mount->getToken()))
		   ->setValue('parent', $qb->createNamedParameter($mount->getParent()))
		   ->setValue('mountpoint', $qb->createNamedParameter($mount->getMountPoint()))
		   ->setValue('mountpoint_hash', $qb->createNamedParameter(md5($mount->getMountPoint())));

		$qb->execute();
	}


	/**
	 * @param string $token
	 */
	public function delete(string $token): void {
		$qb = $this->getMountDeleteSql();
		$qb->limitToToken($token);

		$qb->execute();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return RemoteMount[]
	 * @throws RequestBuilderException
	 */
	public function getForUser(IFederatedUser $federatedUser): array {
		$qb = $this->getMountSelectSql();
		$qb->setOptions([CoreQueryBuilder::MOUNT], ['getData' => true]);
		$qb->leftJoinMember(CoreQueryBuilder::MOUNT);
		$qb->leftJoinMountpoint(CoreQueryBuilder::MOUNT);
		$qb->limitToInitiator(CoreQueryBuilder::MOUNT, $federatedUser, 'circle_id');

		return $this->getItemsFromRequest($qb);
	}
}
