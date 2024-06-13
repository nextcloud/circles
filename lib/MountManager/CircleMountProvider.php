<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\MountManager;

use Exception;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Mount;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IUser;

class CircleMountProvider implements IMountProvider {
	use TArrayTools;

	public const EXTERNAL_STORAGE = ExternalStorage::class;

	public function __construct(
		private IClientService $clientService,
		private CircleMountManager $circleMountManager,
		private ICloudIdManager $cloudIdManager,
		private MountRequest $mountRequest,
		private FederatedUserService $federatedUserService,
		private ConfigService $configService
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
				//				if ($share->getMountPoint() !== '-') {
				//					$this->fixDuplicateFile($user->getUID(), $gss.share);
				$mounts[] = $this->generateCircleMount($item, $loader);
				//				}
			} catch (Exception $e) {
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
}
