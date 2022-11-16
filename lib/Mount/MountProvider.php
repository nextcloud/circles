<?php

namespace OCA\Circles\Mount;

use Exception;
use OC\User\NoUserException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Mount\FolderMount;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotFoundException as FilesNotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class MountProvider implements IMountProvider {
	private IRootFolder $rootFolder;
	private LoggerInterface $logger;
	private CircleRequest $circleRequest;
	private FederatedUserService $federatedUserService;
	private ConfigService $configService;
	private CirclesFolderManager $circlesFolderManager;

	/**
	 * MountProvider constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param LoggerInterface $logger
	 * @param CircleRequest $circleRequest
	 * @param FederatedUserService $federatedUserService
	 * @param ConfigService $configService
	 * @param CirclesFolderManager $collectiveFolderManager
	 */
	public function __construct(
		IRootFolder $rootFolder,
		LoggerInterface $logger,
		CircleRequest $circleRequest,
		FederatedUserService $federatedUserService,
		ConfigService $configService,
		CirclesFolderManager $collectiveFolderManager
	) {
		$this->rootFolder = $rootFolder;
		$this->circlesFolderManager = $collectiveFolderManager;
		$this->logger = $logger;
		$this->circleRequest = $circleRequest;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 * /** Called by core, this will return an array of IMountPoint available to current user
	 * This is done by retrieving FolderMount available to the user and converting each FolderMount
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return IMountPoint[]
	 * @throws FilesNotFoundException
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$folders = $this->getFolderMountsForUser($user);
		try {
			return array_filter(
				array_map(function (FolderMount $folder) use ($user, $loader): ?IMountPoint {
					try {
						return $this->circlesFolderManager->getMount($folder, $loader, $user);
					} catch (Exception $e) {
						return null;
					}
				}, $folders)
			);
		} catch (Exception $e) {
			$this->logger->error('error while getMountsForUser', ['exception' => $e]);

			return [];
		}
	}


	/**
	 * returns list of FolderMount related to current user.
	 * One FolderMount per Circle:
	 *
	 * - mountpoint_enabled must be activated by instance,
	 * - circle must be configured as CFG_MOUNTPOINT,
	 * - current user must be a member of the circle.
	 *
	 * @param IUser $user
	 *
	 * @return FolderMount[]
	 * @throws FilesNotFoundException
	 */
	private function getFolderMountsForUser(IUser $user): array {
		$folderMounts = [];
		if (!$this->configService->getAppValueBool(ConfigService::MOUNTPOINT_ENABLED)) {
			return $folderMounts;
		}

		try {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($user->getUID());
			$circleProbe = new CircleProbe();
			$circleProbe->limitConfig(Circle::CFG_MOUNTPOINT);

			$dataProbe = new DataProbe();
			$dataProbe->add(DataProbe::INITIATOR);
			$circles = $this->circleRequest->probeCircles($federatedUser, $circleProbe, $dataProbe);
		} catch (Exception $e) {
			$this->logger->error('error while probeCircles', ['exception' => $e]);

			return $folderMounts;
		}

		try {
			$userFolder = $this->getUserFolder($user);
		} catch (NotPermittedException $e) {
			return $folderMounts;
		}

		foreach ($circles as $circle) {
			$name = $circle->getSanitizedName();
			$mountPoint = ($this->getCirclesFolderPath($user) === '/') ? '' : ($userFolder->getName() . '/');
			$mountPoint .= ($name !== '') ? $name : $circle->getSingleId();

			$folderMount = new FolderMount($circle->getSingleId());
			$folderMount->setMountPoint2($mountPoint);
			$folderMount->setAbsoluteMountPoint('/' . $user->getUID() . '/files/' . $mountPoint);
			$folderMount->setPermissions(31);

			try {
				$cacheEntry = $this->circlesFolderManager->getCacheEntry($circle->getSingleId());
				$folderMount->setCacheEntry($cacheEntry);
			} catch (Exception $e) {
				$this->logger->error('error while getCacheEntry', ['exception' => $e]);
				continue;
			}

			$folderMounts[] = $folderMount;
		}

		return $folderMounts;
	}


	private function getUserFolder(IUser $user): Folder {
		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		} catch (NotPermittedException | NoUserException $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}

		$userFolderPath = $this->getCirclesFolderPath($user);
		// If collectives path is empty (due to null quota), return userFolder
		if ($userFolderPath === '/') {
			return $userFolder;
		}

		try {
			$circlesFolder = $userFolder->get($userFolderPath);
			// Rename existing node if it's not a folder
			if (!$circlesFolder instanceof Folder) {
				$new = $this->generateFolderName($userFolder, $userFolderPath);
				$circlesFolder->move($userFolder->getPath() . '/' . $new);
				$circlesFolder = $userFolder->newFolder($userFolderPath);
			}
		} catch (FilesNotFoundException $e) {
			try {
				$circlesFolder = $userFolder->newFolder($userFolderPath);
			} catch (NotPermittedException $e) {
				throw new NotPermittedException($e->getMessage(), 0, $e);
			}
		} catch (InvalidPathException $e) {
			throw new NotFoundException($e->getMessage(), 0, $e);
		} catch (NotPermittedException | LockedException $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}

		return $circlesFolder;
	}


	/**
	 * returns the path of Circles Folder, based on:
	 *
	 * - current settings from user (circles/user_folder)
	 * - default settings from the instance (mountpoint_path)
	 *
	 * @param IUser $user
	 *
	 * @return string
	 * @throws NotPermittedException
	 */
	public function getCirclesFolderPath(IUser $user): string {
		$folderPath = $this->configService->getUserValue('user_folder', '', $user->getUID());
		if ($folderPath === '') {
			// Guest users and others with null quota are not allowed to create a subdirectory
			if ($user->getQuota() === '0 B') {
				return '/';
			}

			$folderPath = $this->configService->getAppValue(ConfigService::MOUNTPOINT_PATH);

			try {
				$this->configService->setUserValue('user_folder', $folderPath, $user->getUID());
			} catch (UnexpectedValueException $e) {
				throw new NotPermittedException($e->getMessage(), 0, $e);
			}
		}

		return $folderPath;
	}


	/**
	 * generate a new folder name based on already existing folder
	 *
	 * @param Folder $folder
	 * @param string $filename
	 * @param int $loop
	 *
	 * @return string
	 */
	private function generateFolderName(Folder $folder, string $filename, int $loop = 1): string {
		$path = $filename;
		$path .= ($loop > 1) ? ' (' . $loop . ')' : '';

		if (!$folder->nodeExists($filename) && !$folder->nodeExists($path)) {
			return $filename;
		}

		return $this->generateFolderName($folder, $filename, ++$loop);
	}
}
