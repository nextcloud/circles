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


use OCA\Circles\Model\Member;
use OCA\Circles\Model\RemoteMount;
use OCP\Files\Config\IMountProvider;

class MountsRequest extends MountsRequestBuilder {


	/**
	 * @param RemoteMount $remote
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(RemoteMount $remote) {

		if ($remote === null) {
			return false;
		}

		try {
			$cloud = $remote->getCloud();

			$qb = $this->getRemoteMountsInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($remote->getCircleId()))
			   ->setValue('remote_circle_id', $qb->createNamedParameter($remote->getRemoteCircleId()))
			   ->setValue('cloud_id', $qb->createNamedParameter($cloud->getCloudId()))
			   ->setValue('token', $qb->createNamedParameter($remote->getToken()))
			   ->setValue('password', $qb->createNamedParameter($remote->getPassword()))
			   ->setValue('file_id', $qb->createNamedParameter($remote->getFileId()))
			   ->setValue('file_name', $qb->createNamedParameter($remote->getFilename()))
			   ->setValue('author', $qb->createNamedParameter($remote->getAuthor()))
			   ->setValue('mountpoint', $qb->createNamedParameter($remote->getMountPoint()))
			   ->setValue('mountpoint_hash', $qb->createNamedParameter($remote->getMountPointHash()));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	public function getRemoteMountsForUser($userId) {
		$qb = $this->getRemoteMountsSelectSql();
		$this->limitToMember($qb, $userId, Member::TYPE_USER, '`rm`.`circle_id`');
		$this->rightJoinClouds($qb, 'cloud_id');

		$mounts = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
//			\OC::$server->getLogger()
//						->log(2, '___' . json_encode($data));
			$mounts[] = $this->parseRemoteMountsSelectSql($data);
		}
		$cursor->closeCursor();

		return $mounts;
	}
}