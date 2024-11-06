<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\MountManager;

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
use OCA\Circles\Tools\Traits\TArrayTools;
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

class CircleMountProvider implements IMountProvider {
	use TArrayTools;

	public const EXTERNAL_STORAGE = ExternalStorage::class;

	public function __construct(
		private IClientService $clientService,
		private IRootFolder $rootFolder,
		private CircleMountManager $circleMountManager,
		private ICloudIdManager $cloudIdManager,
		private MountRequest $mountRequest,
		private MountPointRequest $mountPointRequest,
		private FederatedUserService $federatedUserService,
		private ConfigService $configService,
		private LoggerInterface $logger,
	) {
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
				$this->fixDuplicateFile($user->getUID(), $item);
				$mounts[] = $this->generateCircleMount($item, $loader);
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
