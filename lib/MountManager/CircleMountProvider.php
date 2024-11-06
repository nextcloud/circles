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


namespace OCA\Circles\MountManager;

use OCA\Circles\Tools\Traits\TArrayTools;
use Exception;
use OC\DB\Exceptions\DbalException;
use OCA\Circles\Db\MountPointRequest;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MountNotFoundException;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\Mountpoint;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use Psr\Log\LoggerInterface;

/**
 * Class CircleMountProvider
 *
 * @package OCA\Circles\MountManager
 */
class CircleMountProvider implements IMountProvider {
	use TArrayTools;


//	const LOCAL_STORAGE = ::class;
	public const EXTERNAL_STORAGE = ExternalStorage::class;


	/** @var IClientService */
	private $clientService;

	/** @var CircleMountManager */
	private $circleMountManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var MountRequest */
	private $mountRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MountProvider constructor.
	 *
	 * @param IClientService $clientService
	 * @param CircleMountManager $circleMountManager
	 * @param ICloudIdManager $cloudIdManager
	 * @param MountRequest $mountRequest
	 * @param FederatedUserService $federatedUserService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IClientService $clientService,
		CircleMountManager $circleMountManager,
		ICloudIdManager $cloudIdManager,
		MountRequest $mountRequest,
		FederatedUserService $federatedUserService,
		ConfigService $configService
	) {
		$this->clientService = $clientService;
		$this->circleMountManager = $circleMountManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->mountRequest = $mountRequest;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return IMountPoint[]
	 * @throws RequestBuilderException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($user->getUID());
		$items = $this->mountRequest->getForUser($federatedUser);

		$mounts = [];
		foreach ($items as $item) {
			try {
//				if ($share->getMountPoint() !== '-') {
//					$this->fixDuplicateFile($user->getUID(), $gss.share);
				$mounts[] = $this->generateCircleMount($item, $loader);
//				}
			} catch (Exception $e) {
				$this->logger->warning('issue with teams\' mounts', ['exception' => $e]);
			}
		}

		return $mounts;
	}


	/**
	 * @param Mount $mount
	 * @param IStorageFactory $storageFactory
	 *
	 * @return CircleMount
	 * @throws InitiatorNotFoundException
	 * @throws MountPointConstructionException
	 */
	public function generateCircleMount(Mount $mount, IStorageFactory $storageFactory): CircleMount {
		$initiator = $mount->getInitiator();

		// TODO: right now, limited to Local Nextcloud User
		if ($initiator->getInheritedBy()->getUserType() !== Member::TYPE_USER
			|| !$this->configService->isLocalInstance($initiator->getInheritedBy()->getInstance())) {
			throw new InitiatorNotFoundException();
		}

		$mount->setCloudIdManager($this->cloudIdManager)
			  ->setHttpClientService($this->clientService)
//		->setStorage(self::EXTERNAL_STORAGE)
			  ->setMountManager($this->circleMountManager);

		return new CircleMount(
			$mount,
			self::EXTERNAL_STORAGE,
			$storageFactory
		);
	}


	/**
	 * @param int $gsShareId
	 * @param string $target
	 *
	 * @return bool
	 */
	public function renameShare(int $gsShareId, string $target) {
//		try {
//			if ($target !== '-') {
//				$target = $this->stripPath($target);
//				$this->gsSharesRequest->getShareMountPointByPath($this->userId, $target);
//
//				return false;
//			}
//		} catch (ShareNotFound $e) {
//		}
//
//		$mountPoint = new GSShareMountpoint($gsShareId, $this->userId, $target);
//		try {
//			$this->gsSharesRequest->getShareMountPointById($gsShareId, $this->userId);
//			$this->gsSharesRequest->updateShareMountPoint($mountPoint);
//		} catch (ShareNotFound $e) {
//			$this->gsSharesRequest->generateShareMountPoint($mountPoint);
//		}

		return true;
	}


	// TODO: implement !
	public function getMountManager() {
		return $this;
	}

	// TODO: implement !
	public function removeShare($mountPoint) {
	}

	// TODO: implement !
	public function removeMount($mountPoint) {
	}


	/**
	 * @param int $gsShareId
	 *
	 * @return bool
	 */
	public function unshare(int $gsShareId) {
		return $this->renameShare($gsShareId, '-');
	}


	/**
	 * remove '/user/files' from the path and trailing slashes
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function stripPath($path) {
		return $path;
//		$prefix = '/' . $this->userId . '/files';
//
//		return rtrim(substr($path, strlen($prefix)), '/');
	}


	private function fixDuplicateFile(string $userId, Mount $mount): void {
		if ($mount->getOriginalMountPoint() === '-') {
			return;
		}

		$fs = $this->rootFolder->getUserFolder($userId);

		try {
			$fs->get($mount->getMountPoint());
		} catch (NotFoundException) {
			// in case no alternate mountpoint, we generate one in database (easier to catch duplicate mountpoint)
			if ($mount->getAlternateMountPoint() !== null) {
				return;
			}

			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			$mountPoint = new Mountpoint($mount->getMountId(), $federatedUser->getSingleId(), $mount->getOriginalMountPoint());
			try {
				$this->mountPointRequest->insert($mountPoint);
				return;
			} catch (DbalException $e) {
				// meaning a duplicate mountpoint already exists, we need to set a new filename
				if ($e->getReason() !== DbalException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e;
				}
			}
		}

		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$this->generateIncrementedMountpoint($fs, $mount, $federatedUser);
	}

	private function generateIncrementedMountpoint(Folder $fs, Mount $mount, IFederatedUser $federatedUser): void {
		$info = pathinfo($mount->getMountPoint());
		$filename = rtrim($this->get('dirname', $info), '/') . '/' . $this->get('filename', $info);
		$extension = $this->get('extension', $info);
		$extension = ($extension === '') ? '' : '.' . $extension;

		$n = 2;
		while (true) {
			$path = $filename . " ($n)" . $extension;
			try {
				$fs->get($path);
			} catch (NotFoundException) {
				$mountPoint = new Mountpoint($mount->getMountId(), $federatedUser->getSingleId(), $path);
				$mount->setAlternateMountPoint($mountPoint);
				try {
					try {
						$this->mountPointRequest->update($mountPoint);
					} catch (MountNotFoundException) {
						$this->mountPointRequest->insert($mountPoint);
					}
					return;
				} catch (DbalException $e) {
					// meaning path is already used by another share for this user, we keep incrementing
					if ($e->getReason() !== DbalException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
				}
			}

			$n++;
		}
	}
}
