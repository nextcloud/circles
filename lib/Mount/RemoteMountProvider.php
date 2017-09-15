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

namespace OCA\Circles\Mount;

use OCA\Circles\Db\MountsRequest;
use OCA\Circles\Model\RemoteMount;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\Mount;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;


class RemoteMountProvider implements IMountProvider {

	const REMOTE_STORAGE = '\OCA\Circles\Mount\RemoteStorage';

	/** @var MountsRequest */
	private $mountsRequest;

	/** @var Manager */
	private $managerProvider;

	/** @var ICloudIdManager */
	private $cloudIdManager;


	/**
	 * @param MountsRequest $mountsRequest
	 * @param Manager $managerProvider
	 * @param ICloudIdManager $cloudIdManager
	 */
	public function __construct(
		MountsRequest $mountsRequest, Manager $managerProvider, ICloudIdManager $cloudIdManager
	) {
		$this->mountsRequest = $mountsRequest;
		$this->managerProvider = $managerProvider;
		$this->cloudIdManager = $cloudIdManager;
	}


	/**
	 * {@inheritdoc}
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {

		$NCMounts = [];
		$mounts = $this->mountsRequest->getRemoteMountsForUser($user->getUID());
		foreach ($mounts as $mount) {
			$NCMounts[] = $this->generateNCMountFromRemoteMount($user->getUID(), $mount, $loader);
		}


		return $NCMounts;
	}


	private function generateNCMountFromRemoteMount(
		$userId, RemoteMount $mount, IStorageFactory $storageFactory
	) {
		//	$managerProvider = $this->managerProvider;
		//	$data['manager'] = $managerProvider();
		$data['manager'] = $this->managerProvider;
		$data['mountpoint'] = '/' . $userId . '/files/' . ltrim($mount->getMountPoint(), '/');
		$data['cloudId'] = $this->cloudIdManager->getCloudId(
			$mount->getAuthor(), $mount->getRemoteCloud()
									   ->getAddress()
		);

		$data['owner'] = $userId;
		$data['remote'] = $mount->getRemoteCloud()
								->getAddress();
		$data['password'] = '';
		$data['token'] = $mount->getToken();
		$data['share_token'] = $mount->getToken();

		$data['certificateManager'] = \OC::$server->getCertificateManager($userId);
		$data['HttpClientService'] = \OC::$server->getHTTPClientService();

		\OC::$server->getLogger()
					->log(2, '@@@@ 2 ' . json_encode($data));

		return new Mount(
			Manager::STORAGE, $data['mountpoint'], $data, $data['manager'], $storageFactory
		);
	}

//		$mounts = [];
//		while ($row = $query->fetch()) {
//			$row['manager'] = $this;
//			$row['token'] = $row['share_token'];
//			$mounts[] = $this->getMount($user, $row, $loader);
//		}
//		return $mounts;
//	}

}
