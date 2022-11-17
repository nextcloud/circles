<?php

namespace OCA\Circles\Mount;

use Exception;
use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheEntry;
use OC\Files\Node\LazyFolder;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\Mount\FolderMount;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class CirclesFolderManager {
	private const LANDING_PAGE_TITLE = 'Readme';
	private const SUFFIX = '.md';

	private IRootFolder $rootFolder;
	private IDBConnection $connection;
	private IMimeTypeLoader $mimeTypeLoader;
	private IConfig $config;
	private IUserSession $userSession;
	private IRequest $request;
	private ?string $rootPath = null;

	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $connection,
		IMimeTypeLoader $mimeTypeLoader,
		IConfig $config,
		IUserSession $userSession,
		IRequest $request
	) {
		$this->rootFolder = $rootFolder;
		$this->connection = $connection;
		$this->mimeTypeLoader = $mimeTypeLoader;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->request = $request;
	}

	public function getRootPath(): string {
		if (null !== $this->rootPath) {
			return $this->rootPath;
		}

		$instanceId = $this->config->getSystemValue('instanceid', null);
		if (null === $instanceId) {
			throw new \RuntimeException('no instance id!');
		}

		$this->rootPath = 'appdata_' . $instanceId . '/' . Application::APP_FOLDER;

		return $this->rootPath;
	}

	public function getCircleRootPath(string $circleId): string {
		return $this->getRootPath() . '/' . $circleId;
	}


	/**
	 * @return Folder
	 */
	public function getRootFolder(): Folder {
		$rootFolder = $this->rootFolder;

		return (new LazyFolder(function () use ($rootFolder) {
			try {
				return $rootFolder->get($this->getRootPath());
			} catch (NotFoundException $e) {
				return $rootFolder->newFolder($this->getRootPath());
			}
		}));
	}

	/**
	 * @return string|null
	 */
	private function getCurrentUID(): ?string {
		try {
			// wopi requests are not logged in, instead we need to get the editor user from the access token
			if (strpos($this->request->getRawPathInfo(), 'apps/richdocuments/wopi')
				&& class_exists(
					'OCA\Richdocuments\Db\WopiMapper'
				)) {
				$wopiMapper = \OC::$server->query('OCA\Richdocuments\Db\WopiMapper');
				$token = $this->request->getParam('access_token');
				if ($token) {
					$wopi = $wopiMapper->getPathForToken($token);

					return $wopi->getEditorUid();
				}
			}
		} catch (Exception $e) {
		}

		$user = $this->userSession->getUser();

		return $user ? $user->getUID() : null;
	}

	/**
	 * @param FolderMount $folderMount
	 * @param IStorageFactory|null $loader
	 * @param IUser|null $user
	 *
	 * @return IMountPoint|null
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws Exception
	 */
	public function getMount(
		FolderMount $folderMount,
		IStorageFactory $loader,
		?IUser $user = null
	): ?IMountPoint {
		if (!$folderMount->hasCacheEntry()) {
			try {
				$folder = $this->getFolder($folderMount->getCircleId());
			} catch (InvalidPathException | NotPermittedException $e) {
				return null;
			}

			$cacheEntry = $this->getRootFolder()->getStorage()->getCache()->get($folder->getId());
		} else {
			$cacheEntry = $folderMount->getCacheEntry();
		}

		$storage = new NoExcludePropagatorStorageWrapper(['storage' => $this->getRootFolder()->getStorage()]);

		$current = $this->userSession->getUser();
		if ($user !== null && $current !== null) {
			$storage = new ACLStorageWrapper(
				[
					'storage' => $storage,
					'permissions' => $folderMount->getPermissions(),
					'in_share' => ($current->getUID() !== $user->getUID())
				]
			);
			$cacheEntry['permissions'] &= $folderMount->getPermissions();
		}

		$baseStorage = new Jail(
			[
				'storage' => $storage,
				'root' => $this->getRootFolder()->getInternalPath() . '/' . $folderMount->getCircleId()
			]
		);
		$circlesStorage = new CirclesFolderStorage([
													   'storage' => $baseStorage,
													   'rootCacheEntry' => $cacheEntry,
													   'mountOwner' => $user
												   ]);
		$maskedStorage = new PermissionsMask([
												 'storage' => $circlesStorage,
												 'mask' => $folderMount->getPermissions()
											 ]);

		return new CirclesFolderMountPoint(
			$folderMount,
			$this,
			$maskedStorage,
			$loader
		);
	}


	private function getRootFolderStorageId(): int {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('fileid')
		   ->from('filecache')
		   ->where(
			   $qb->expr()->eq(
				   'storage', $qb->createNamedParameter(
				   $this->getRootFolder()->getStorage()->getCache()->getNumericStorageId()
			   )
			   )
		   )
		   ->andWhere($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($this->getRootPath()))));

		return (int)$qb->execute()->fetchColumn();
	}


	/**
	 * @param string $path
	 * @param string $lang
	 *
	 * @return string
	 */
	public function getLandingPagePath(string $path, string $lang): string {
		$landingPagePathEnglish = $path . '/' . self::LANDING_PAGE_TITLE . '.en' . self::SUFFIX;
		$landingPagePathLocalized = $path . '/' . self::LANDING_PAGE_TITLE . '.' . $lang . self::SUFFIX;

		return file_exists($landingPagePathLocalized) ? $landingPagePathLocalized : $landingPagePathEnglish;
	}


	public function getCacheEntry(string $circleId): ?CacheEntry {
		$cacheEntry = $this->getFolderFileCache($circleId);

		if (isset($cacheEntry['fileid'])) {
			return Cache::cacheEntryFromData($cacheEntry, $this->mimeTypeLoader);
		}

		return null;
	}


	/**
	 * @param int $circleId
	 * @param string $name
	 *
	 * @return array
	 * @throws NotFoundException
	 * @throws \OCP\DB\Exception
	 */
	private function getFolderFileCache(string $circleId): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select(
			'fileid',
			'storage',
			'path',
			'mimetype',
			'mimepart',
			'size',
			'mtime',
			'storage_mtime',
			'etag',
			'encrypted',
			'parent',
			'permissions'
		)
		   ->from('filecache', 'c')
		   ->where(
			   $qb->expr()->andX(
				   $qb->expr()->eq(
					   'path_hash', $qb->createNamedParameter(md5($this->getCircleRootPath($circleId)))
				   ),
				   $qb->expr()->eq('parent', $qb->createNamedParameter($this->getRootFolderStorageId()))
			   )
		   );

		$cache = $qb->execute()->fetch();
		$cache['mount_point'] = $circleId;

		return $cache;
	}


	/**
	 * returns folder assigned to circleId
	 * creates folder if not available
	 *
	 * @param string $circleId
	 *
	 * @return Folder
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 */
	public function getFolder(string $circleId): Folder {
		try {
			$folder = $this->getRootFolder()->get($circleId);
			if (!$folder instanceof Folder) {
				throw new InvalidPathException('Not a folder: ' . $folder->getPath());
			}
		} catch (NotFoundException $e) {
			$folder = $this->getRootFolder()->newFolder($circleId);
		}

		return $folder;
	}
}
