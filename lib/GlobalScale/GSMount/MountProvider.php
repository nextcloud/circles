<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
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


namespace OCA\Circles\GlobalScale\GSMount;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC;
use OCA\Circles\Db\GSSharesRequest;
use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCA\Circles\Service\ConfigService;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

/**
 * Class MountProvider
 *
 * @package OCA\Circles\GlobalScale\GSMount
 */
class MountProvider implements IMountProvider {
	use TArrayTools;


	public const STORAGE = '\OCA\Files_Sharing\External\Storage';


	/** @var MountManager */
	private $mountManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var GSSharesRequest */
	private $gsSharesRequest;

	/** @var ConfigService */
	private $configService;

	/**
	 * MountProvider constructor.
	 *
	 * @param MountManager $mountManager
	 * @param ICloudIdManager $cloudIdManager
	 * @param GSSharesRequest $gsSharesRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		MountManager $mountManager, ICloudIdManager $cloudIdManager, GSSharesRequest $gsSharesRequest,
		ConfigService $configService
	) {
		$this->mountManager = $mountManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->gsSharesRequest = $gsSharesRequest;
		$this->configService = $configService;
	}


	/**
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$shares = $this->gsSharesRequest->getForUser($user->getUID());

		$mounts = [];
		foreach ($shares as $share) {
			try {
				if ($share->getMountPoint() !== '-') {
					$this->fixDuplicateFile($user->getUID(), $share);
					$mounts[] = $this->generateMount($share, $user->getUID(), $loader);
				}
			} catch (Exception $e) {
			}
		}

		return $mounts;
	}


	/**
	 * @param GSShare $share
	 * @param string $userId
	 * @param IStorageFactory $storageFactory
	 *
	 * @return Mount
	 * @throws Exception
	 */
	public function generateMount(
		GSShare $share, string $userId, IStorageFactory $storageFactory
	) {
		$protocol = 'https';
		if ($this->configService->isLocalNonSSL()) {
			$protocol = 'http';
		}

		$data = $share->toMount($userId, $protocol);
		$data['manager'] = $this->mountManager;
		$data['gsShareId'] = $share->getId();
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = OC::$server->getCertificateManager($userId);
		$data['HttpClientService'] = OC::$server->getHTTPClientService();

		return new Mount(
			self::STORAGE, $share->getMountPoint($userId), $data, $this->mountManager, $storageFactory
		);
	}


	/**
	 * @param string $userId
	 * @param GSShare $share
	 *
	 * @throws OC\User\NoUserException
	 * @throws NotPermittedException
	 */
	private function fixDuplicateFile(string $userId, GSShare $share) {
		$fs = \OC::$server->getRootFolder()
						  ->getUserFolder($userId);

		try {
			$fs->get($share->getMountPoint());
		} catch (NotFoundException $e) {
			return;
		}

		$info = pathinfo($share->getMountPoint());
		$filename = $this->get('dirname', $info) . '/' . $this->get('filename', $info);
		$extension = $this->get('extension', $info);
		$extension = ($extension === '') ? '' : '.' . $extension;

		$n = 2;
		while (true) {
			$path = $filename . " ($n)" . $extension;
			try {
				$fs->get($path);
			} catch (NotFoundException $e) {
				$share->setMountPoint($path);
				$mountPoint = new GSShareMountpoint($share->getId(), $userId, $path);
				$this->gsSharesRequest->updateShareMountPoint($mountPoint);

				return;
			}

			$n++;
		}
	}
}
