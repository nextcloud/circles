<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\MountManager;

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
use OCA\Files_Sharing\External\Manager as ExternalShareManager;
use OCA\Files_Sharing\External\Storage as ExternalStorage;
use OCP\DB\Exception;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IPartialMountProvider;
use OCP\Files\Config\MountProviderArgs;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use Override;
use Psr\Log\LoggerInterface;

class CircleMountProvider implements IMountProvider, IPartialMountProvider {
	use TArrayTools;

	/** @var class-string<ExternalStorage> */
	public const EXTERNAL_STORAGE = ExternalStorage::class;
    public array $mountedPoint = [];

	public function __construct(
		private IClientService $clientService,
		private IRootFolder $rootFolder,
		private ICloudIdManager $cloudIdManager,
		private MountRequest $mountRequest,
		private MountPointRequest $mountPointRequest,
		private FederatedUserService $federatedUserService,
		private ConfigService $configService,
		private LoggerInterface $logger,
		private ExternalShareManager $externalShareManager,
	) {
	}

	/**
	 * @return list<IMountPoint>
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
			} catch (\Exception $e) {
				$this->logger->warning('issue with teams\' mounts', ['exception' => $e]);
			}
		}

		return $mounts;
	}

	/**
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
			->setExternalShareManager($this->externalShareManager);

		return new CircleMount(
			$mount,
			self::EXTERNAL_STORAGE,
			$storageFactory
		);
	}

	private function fixDuplicateFile(string $userId, Mount $mount): void {
		if ($mount->getOriginalMountPoint() === '-') {
			return;
		}

        // we will be using cache version of the filesystem, to not cycle while operating it
        $mountId = $mount->getId();
        $baseFile = $mountPoint = '/files' . $mount->getMountPoint();
        $parentMount = $this->rootFolder->getUserFolder($userId)->getMountPoint();
        $parentCache = $parentMount->getStorage()->getCache();

        // splitting extension to have a nice /file (1).ext
        $ext = pathinfo($mountPoint, PATHINFO_EXTENSION);
        if ($ext !== '') {
            $baseFile = substr($mountPoint, 0, -(strlen($ext) + 1));
            $ext = '.' . $ext;
        }

        // based on cached content, improving naming
        $n = 1;
        while ($parentCache->inCache($mountPoint)
            || ($this->mountedPoint[$mountPoint] ?? $mountId) !== $mountId) {
            $mountPoint = $baseFile . ' (' . $n++ . ')' . $ext;
        }

        // we keep trace as this won't be available in cache
        $this->mountedPoint[$mountPoint] = $mount->getId();

        $federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
        $mountPoint = new Mountpoint($mount->getMountId(), $federatedUser->getSingleId(), substr($mountPoint, 6));
        try {
            try {
                $this->mountPointRequest->update($mountPoint);
            } catch (MountNotFoundException) {
                $this->mountPointRequest->insert($mountPoint);
            }
            return;
        } catch (Exception $e) {
            $this->logger->error('issue while creating alternate mount point', ['exception' => $e]);
            // while there should be no reason for a unique constraint violation, we just log and ignore it
            if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                throw $e;
            }
        }
	}

	#[Override]
	public function getMountsForPath(string $setupPathHint, bool $forChildren, array $mountProviderArgs, IStorageFactory $loader): array {
		/** @var array<string, array{federatedUser: IFederatedUser, paths: string[]}> $userMountRequests */
		$userMountRequests = [];
		/** @var array<string, IMountPoint> $mounts */
		$mounts = [];

		/** @var MountProviderArgs $mountProviderArg */
		foreach ($mountProviderArgs as $mountProviderArg) {
			$user = $mountProviderArg->mountInfo->getUser();

			$parts = explode('/', $mountProviderArg->mountInfo->getMountPoint());
			if ($parts[1] !== $user->getUID() || $parts[2] !== 'files') {
				continue;
			}

			$userMountRequests[$user->getUID()] ??= [
				'federatedUser' => $this->federatedUserService->getLocalFederatedUser($user->getUID()),
				'paths' => [],
			];

			$userMountRequests[$user->getUID()]['paths'][] = '/' . implode('/', array_slice($parts, 3));
		}

		foreach ($userMountRequests as $uid => $userMountRequest) {
			$userItems = $this->mountRequest->getForUser(
				$userMountRequest['federatedUser'],
				$userMountRequest['paths'],
				$forChildren
			);

			foreach ($userItems as $item) {
				$mountPoint = '/' . $uid . '/files' . $item->getMountPoint();
				if (isset($mounts[$mountPoint])) {
					continue;
				}
				try {
					$this->fixDuplicateFile($uid, $item);
					$mounts[$mountPoint] = $this->generateCircleMount($item, $loader);
				} catch (\Exception $e) {
					$this->logger->error('Failed to create Teams mount', ['exception' => $e]);
				}
			}
		}

		return $mounts;
	}
}
