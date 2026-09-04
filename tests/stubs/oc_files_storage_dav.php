<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage;

use Exception;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Filesystem;
use OC\MemCache\ArrayCache;
use OC\OCM\OCMSignatoryManager;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeRemotePropfindEvent;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Lock\LockedException;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\ISignatureManager;
use OCP\Server;
use OCP\Util;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\HTTP\ClientException;
use Sabre\HTTP\ClientHttpException;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface as SabreResponseInterface;

/**
 * Sabre HTTP Client extended with Bearer-token authentication and transparent
 * refresh-on-401: when a request fails with HTTP 401 the client invokes a
 * registered refresh callback once, applies the new token, and replays the
 * request. Callers can use the client normally without thinking about token
 * expiry.
 *
 * @package OC\Files\Storage
 */
class BearerAuthAwareSabreClient extends Client {
	/**
	 * Bearer authentication.
	 */
	public const AUTH_BEARER = 8;

	/** @var (\Closure(): ?string)|null returns a fresh bearer token, or null if it cannot be refreshed */
	private ?\Closure $refreshTokenCallback = null;

	/** Guard against re-entry if the replayed request also returns 401. */
	private bool $retrying = false;

	public function __construct(array $settings) {
		parent::__construct($settings);

		if (isset($settings['bearerToken']) && isset($settings['authType']) && ($settings['authType'] & self::AUTH_BEARER)) {
			$this->applyBearerToken((string)$settings['bearerToken']);
		}
	}

	/**
	 * Register a callback invoked when a request comes back with HTTP 401. The
	 * callback should return a fresh bearer token, or null to give up. When a
	 * non-empty token is returned the failing request is replayed once.
	 *
	 * @param (callable(): ?string)|null $callback
	 */
	public function setRefreshTokenCallback(?callable $callback): void {
		$this->refreshTokenCallback = $callback === null ? null : \Closure::fromCallable($callback);
	}

	#[\Override]
	public function send(RequestInterface $request): SabreResponseInterface {
		try {
			return parent::send($request);
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() !== 401 || $this->retrying || $this->refreshTokenCallback === null) {
				throw $e;
			}
			$this->retrying = true;
			try {
				$newToken = ($this->refreshTokenCallback)();
				if (!is_string($newToken) || $newToken === '') {
					throw $e;
				}
				$this->applyBearerToken($newToken);
				return parent::send($request);
			} finally {
				$this->retrying = false;
			}
		}
	}

	private function applyBearerToken(string $token): void {
		/** @psalm-suppress InvalidArrayOffset */
		$curlType = $this->curlSettings[CURLOPT_HTTPAUTH] ?? 0;
		$curlType |= CURLAUTH_BEARER;
		$this->addCurlSetting(CURLOPT_HTTPAUTH, $curlType);
		$this->addCurlSetting(CURLOPT_XOAUTH2_BEARER, $token);
	}
}

/**
 * Class DAV
 *
 * @package OC\Files\Storage
 */
class DAV extends Common {
	/** @var string */
	protected $password;
	/** @var string */
	protected $user;
	protected ?int $authType = null;
	/** @var string */
	protected $host;
	/** @var bool */
	protected $secure;
	protected bool $verify;
	/** @var string */
	protected $root;
	/** @var string */
	protected $certPath;
	/** @var bool */
	protected $ready;
	/** The resolved bearer token for AUTH_BEARER (access token or exchanged token) */
	protected string $bearerToken = '';
	/** @var Client */
	protected $client;
	/** @var ArrayCache */
	protected $statCache;
	/** @var IClientService */
	protected $httpClientService;
	/** @var ICertificateManager */
	protected $certManager;
	protected LoggerInterface $logger;
	protected IEventLogger $eventLogger;
	protected IMimeTypeDetector $mimeTypeDetector;
	protected IOCMDiscoveryService $discoveryService;
	protected ISignatureManager $signatureManager;
	protected OCMSignatoryManager $signatoryManager;
	protected IAppConfig $appConfig;
	protected IURLGenerator $urlGenerator;

	protected const PROPFIND_PROPS = [
		'{DAV:}getlastmodified',
		'{DAV:}getcontentlength',
		'{DAV:}getcontenttype',
		'{http://owncloud.org/ns}permissions',
		'{http://open-collaboration-services.org/ns}share-permissions',
		'{http://open-cloud-mesh.org/ns}share-permissions',
		'{DAV:}resourcetype',
		'{DAV:}getetag',
		'{DAV:}quota-available-bytes',
	];

	/**
	 * @param array $parameters
	 * @throws \Exception
	 */
	public function __construct(array $parameters)
    {
    }

	protected function init(): void
    {
    }

	/**
	 * Exchange refresh token for access token via the remote server's token endpoint
	 *
	 * @return string The access token
	 * @throws StorageNotAvailableException If token exchange fails
	 */
	protected function exchangeRefreshToken(): string
    {
    }

	/**
	 * Check if bearer authentication is being used
	 */
	protected function isBearerAuth(): bool
    {
    }

	/**
	 * Wrap a Guzzle-based operation with retry-on-401 using the bearer-token
	 * refresh. Sabre-based operations don't need this — {@see BearerAuthAwareSabreClient}
	 * handles 401 transparently on its own.
	 *
	 * @template T
	 * @param callable(): T $operation
	 * @return T
	 * @throws \GuzzleHttp\Exception\ClientException
	 */
	protected function withAuthRetry(callable $operation): mixed
    {
    }

	/**
	 * Exchange the long-lived refresh token for a new short-lived access token
	 * and update {@see $bearerToken} (and the stored password so subsequent
	 * init() calls reuse the same token). Used as the refresh callback for the
	 * Sabre client and by {@see withAuthRetry} for the Guzzle paths.
	 *
	 * @return string|null new access token, or null if the exchange failed
	 */
	protected function refreshAccessToken(): ?string
    {
    }

	/**
	 * Clear the stat cache
	 */
	public function clearStatCache(): void
    {
    }

	#[\Override]
    public function getId(): string
    {
    }

	public function createBaseUri(): string
    {
    }

	#[\Override]
    public function mkdir(string $path): bool
    {
    }

	#[\Override]
    public function rmdir(string $path): bool
    {
    }

	#[\Override]
    public function opendir(string $path)
    {
    }

	/**
	 * @return array<string>
	 */
	protected function getPropfindProperties(): array
    {
    }

	/**
	 *  Get property value from cached PROPFIND response.
	 *  For accessing app-specific properties not included in getMetaData().
	 *
	 * @param string $path
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getPropfindPropertyValue(string $path, string $propertyName): mixed
    {
    }

	/**
	 * Propfind call with cache handling.
	 *
	 * First checks if information is cached.
	 * If not, request it from the server then store to cache.
	 *
	 * @param string $path path to propfind
	 *
	 * @return array|false propfind response or false if the entry was not found
	 *
	 * @throws ClientHttpException
	 */
	protected function propfind(string $path): array|false
    {
    }

	#[\Override]
    public function filetype(string $path): string|false
    {
    }

	#[\Override]
    public function file_exists(string $path): bool
    {
    }

	#[\Override]
    public function unlink(string $path): bool
    {
    }

	#[\Override]
    public function fopen(string $path, string $mode)
    {
    }

	public function writeBack(string $tmpFile, string $path): void
    {
    }

	#[\Override]
    public function free_space(string $path): int|float|false
    {
    }

	#[\Override]
    public function touch(string $path, ?int $mtime = null): bool
    {
    }

	#[\Override]
    public function file_put_contents(string $path, mixed $data): int|float|false
    {
    }

	protected function uploadFile(string $path, string $target): void
    {
    }

	#[\Override]
    public function rename(string $source, string $target): bool
    {
    }

	#[\Override]
    public function copy(string $source, string $target): bool
    {
    }

	#[\Override]
    public function getMetaData(string $path): ?array
    {
    }

	#[\Override]
    public function stat(string $path): array|false
    {
    }

	#[\Override]
    public function getMimeType(string $path): string|false
    {
    }

	#[\Override]
    public function cleanPath(string $path): string
    {
    }

	/**
	 * URL encodes the given path but keeps the slashes
	 *
	 * @param string $path to encode
	 * @return string encoded path
	 */
	protected function encodePath(string $path): string
    {
    }

	/**
	 * @return bool
	 * @throws StorageInvalidException
	 * @throws StorageNotAvailableException
	 */
	protected function simpleResponse(string $method, string $path, ?string $body, int $expected): bool
    {
    }

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies(): bool
    {
    }

	#[\Override]
    public function isUpdatable(string $path): bool
    {
    }

	#[\Override]
    public function isCreatable(string $path): bool
    {
    }

	#[\Override]
    public function isSharable(string $path): bool
    {
    }

	#[\Override]
    public function isDeletable(string $path): bool
    {
    }

	#[\Override]
    public function getPermissions(string $path): int
    {
    }

	#[\Override]
    public function getETag(string $path): string|false
    {
    }

	protected function parsePermissions(string $permissionsString): int
    {
    }

	#[\Override]
    public function hasUpdated(string $path, int $time): bool
    {
    }

	/**
	 * Interpret the given exception and decide whether it is due to an
	 * unavailable storage, invalid storage or other.
	 * This will either throw StorageInvalidException, StorageNotAvailableException
	 * or do nothing.
	 *
	 * @param Exception $e sabre exception
	 * @param string $path optional path from the operation
	 *
	 * @throws StorageInvalidException if the storage is invalid, for example
	 *                                 when the authentication expired or is invalid
	 * @throws StorageNotAvailableException if the storage is not available,
	 *                                      which might be temporary
	 * @throws ForbiddenException if the action is not allowed
	 */
	protected function convertException(Exception $e, string $path = ''): void
    {
    }

	#[\Override]
    public function getDirectoryContent(string $directory): \Traversable
    {
    }
}
