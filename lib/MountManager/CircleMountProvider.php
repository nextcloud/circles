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

use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\MountManager\Model\CirclesFolderMount;
use OCA\Circles\MountManager\Model\Mount;
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

/**
 * Class CircleMountProvider
 *
 * @package OCA\Circles\MountManager
 */
class CircleMountProvider implements IMountProvider {
	use TArrayTools;

	public const LOCAL_STORAGE = CirclesFolderStorage::class;
	public const EXTERNAL_STORAGE = ExternalStorage::class;

	private IClientService $clientService;
	private CircleMountManager $circleMountManager;
	private ICloudIdManager $cloudIdManager;
	private MountRequest $mountRequest;

	private CircleRequest $circleRequest;
	private FederatedUserService $federatedUserService;
	private ConfigService $configService;

	public function __construct(
		IClientService $clientService,
		CircleMountManager $circleMountManager,
		ICloudIdManager $cloudIdManager,
		MountRequest $mountRequest,
		CircleRequest $circleRequest,
		FederatedUserService $federatedUserService,
		ConfigService $configService
	) {
		$this->clientService = $clientService;
		$this->circleMountManager = $circleMountManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->mountRequest = $mountRequest;
		$this->circleRequest = $circleRequest;
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

		return array_merge(
			$this->getRemoteMounts($loader, $federatedUser),
			$this->getCirclesMounts($loader, $federatedUser)
		);
	}


	/**
	 * @param Mount $mount
	 * @param IStorageFactory $storageFactory
	 *
	 * @return CircleMount
	 * @throws InitiatorNotFoundException
	 * @throws MountPointConstructionException
	 */
	private function generateCircleMount(Mount $mount, IStorageFactory $storageFactory): CircleMount {
//		$initiator = $mount->getInitiator();
//
//		// TODO: right now, limited to Local Nextcloud User
//		if ($initiator->getInheritedBy()->getUserType() !== Member::TYPE_USER
//			|| !$this->configService->isLocalInstance($initiator->getInheritedBy()->getInstance())) {
//			throw new InitiatorNotFoundException();
//		}

		$mount->setMountManager($this->circleMountManager);

		return new CircleMount(
			$mount,
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


	/**
	 * @param IStorageFactory $factory
	 * @param IFederatedUser $user
	 *
	 * @return CircleMount[]
	 * @throws RequestBuilderException
	 */
	private function getRemoteMounts(IStorageFactory $factory, IFederatedUser $user): array {
		if (!$this->configService->isGSAvailable()) {
			return [];
		}

		$mounts = [];
		$items = $this->mountRequest->getForUser($user);
		foreach ($items as $mount) {
			try {
				$mount->setCloudIdManager($this->cloudIdManager)
					  ->setHttpClientService($this->clientService)
					  ->setStorage(self::EXTERNAL_STORAGE);

				$mounts[] = $this->generateCircleMount($mount, $factory);
			} catch (Exception $e) {
			}
		}

		return $mounts;
	}


	/**
	 * @param IStorageFactory $factory
	 * @param IFederatedUser $user
	 *
	 * @return CirclesFolderMount[]
	 * @throws RequestBuilderException
	 */
	private function getCirclesMounts(IStorageFactory $factory, IFederatedUser $user): array {
		$mounts = [];

		$circleProbe = new CircleProbe();
		$circleProbe->limitConfig(Circle::CFG_MOUNTPOINT);
		$dataProbe = new DataProbe();
		$dataProbe->add(DataProbe::INITIATOR);

		try {
			$circles = $this->circleRequest->probeCircles($user, $circleProbe, $dataProbe);
		} catch (Exception $e) {
		}

		foreach ($circles as $circle) {
			$mount = new CirclesFolderMount();

			$mount->setCircleId($circle->getSingleId());
			$mount->setParent(-1);
			$mount->setMountPoint('/' . $circle->getSingleId() . '/');
			$mount->setMountPointHash(md5($mount->getMountPoint(true)));
			$mount->setStorage(self::LOCAL_STORAGE);

//			$mount->setInitiator($circle->getInitiator());

			$mounts[] = $this->generateCircleMount($mount, $factory);
		}

		return $mounts;
	}



//		$items = $this->mountRequest->getForUser($user);
//		foreach ($items as $mount) {
//			try {
//				$mount->setCloudIdManager($this->cloudIdManager)
//					  ->setHttpClientService($this->clientService)
//					  ->setMountManager($this->circleMountManager);
//
//				$mounts[] = $this->generateCircleMount($item, $factory);
//			} catch (Exception $e) {
//			}
//		}

}
