<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\External;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use OC\Files\Storage\BearerAuthAwareSabreClient;
use OC\Files\Storage\DAV;
use OC\ForbiddenException;
use OCA\Files_Sharing\External\Manager as ExternalShareManager;
use OCA\Files_Sharing\ISharedStorage;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Federation\ICloudId;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IWatcher;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;

class Storage extends DAV implements ISharedStorage, IDisableEncryptionStorage, IReliableEtagStorage {
	protected IAppConfig $appConfig;

	private const int REFRESH_MAX_ATTEMPTS = 3;
	private const int REFRESH_BACKOFF_SECONDS = 5;

	/**
	 * @param array{HttpClientService: IClientService, manager: ExternalShareManager, cloudId: ICloudId, mountpoint: string, token: string, access_token: ?string, access_token_expires: ?int}|array $options
	 */
	public function __construct($options)
    {
    }

	/**
	 * Refresh the access token. Extends parent to also persist to database.
	 *
	 * Uses expiry timestamps instead of a boolean flag so that concurrent
	 * processes can detect that another process already obtained a fresh token
	 * and reuse it rather than performing a redundant exchange.
	 *
	 * After a failed exchange, a 60-second backoff is applied so that
	 * subsequent file operations do not hammer the remote token endpoint.
	 * The DB is still consulted during backoff in case a concurrent process
	 * succeeded; only the outgoing exchange call is suppressed.
	 *
	 * @return string|null the access token (freshly exchanged or reused from
	 *                     DB), or null if refresh is currently not possible
	 */
	#[\Override]
    protected function refreshAccessToken(): ?string
    {
    }

	#[\Override]
    public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher
    {
    }

	public function getRemoteUser(): string
    {
    }

	public function getRemote(): string
    {
    }

	public function getMountPoint(): string
    {
    }

	public function getToken(): string
    {
    }

	public function getPassword(): ?string
    {
    }

	#[\Override]
    public function getId(): string
    {
    }

	#[\Override]
    public function getCache(string $path = '', ?IStorage $storage = null): ICache
    {
    }

	#[\Override]
    public function getScanner(string $path = '', ?IStorage $storage = null): IScanner
    {
    }

	#[\Override]
    public function hasUpdated(string $path, int $time): bool
    {
    }

	#[\Override]
    public function test(): bool
    {
    }

	/**
	 * Check whether this storage is permanently or temporarily
	 * unavailable
	 *
	 * @throws StorageNotAvailableException
	 * @throws StorageInvalidException
	 */
	public function checkStorageAvailability(): void
    {
    }

	#[\Override]
    public function file_exists(string $path): bool
    {
    }

	/**
	 * Check if the configured remote is a valid-federated share provider
	 */
	protected function testRemote(): bool
    {
    }

	/**
	 * Check whether the remote is an ownCloud/Nextcloud. This is needed since some sharing
	 * features are not standardized.
	 *
	 * @throws LocalServerException
	 */
	public function remoteIsOwnCloud(): bool
    {
    }

	/**
	 * @return mixed
	 * @throws ForbiddenException
	 * @throws NotFoundException
	 * @throws \Exception
	 */
	public function getShareInfo(int $depth = -1)
    {
    }

	#[\Override]
    public function getOwner(string $path): string|false
    {
    }

	#[\Override]
    public function isSharable(string $path): bool
    {
    }

	#[\Override]
    public function getPermissions(string $path): int
    {
    }

	#[\Override]
    public function needsPartFile(): bool
    {
    }

	/**
	 * Translate OCM Permissions to Nextcloud permissions
	 *
	 * @param string $ocmPermissions json encoded OCM permissions
	 * @param string $path path to file
	 * @return int
	 */
	protected function ocmPermissions2ncPermissions(string $ocmPermissions, string $path): int
    {
    }

	/**
	 * Calculate the default permissions in case no permissions are provided
	 */
	protected function getDefaultPermissions(string $path): int
    {
    }

	#[\Override]
    public function free_space(string $path): int|float|false
    {
    }
}
