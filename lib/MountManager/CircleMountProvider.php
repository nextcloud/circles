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
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IUser;

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
