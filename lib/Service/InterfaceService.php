<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Class InterfaceService
 *
 * @package OCA\Circles\Service
 */
class InterfaceService {
	public const IFACE0 = 1;
	public const IFACE1 = 2;
	public const IFACE2 = 3;
	public const IFACE3 = 4;
	public const IFACE4 = 5;
	public const IFACE_INTERNAL = 6;
	public const IFACE_FRONTAL = 7;
	public const IFACE_TEST = 99;

	public const LIST_IFACE = [
		self::IFACE_INTERNAL => 'internal',
		self::IFACE_FRONTAL => 'frontal',
		self::IFACE0 => 'iface0',
		self::IFACE1 => 'iface1',
		self::IFACE2 => 'iface2',
		self::IFACE3 => 'iface3',
		self::IFACE4 => 'iface4',
	];


	use TStringTools;
	use TArrayTools;
	use TNCLogger;


	/** @var int */
	private $currentInterface = 0;

	/** @var int */
	private $outgoingInterface = 0;


	/**
	 * InterfaceService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param RemoteRequest $remoteRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		private IURLGenerator $urlGenerator,
		private RemoteRequest $remoteRequest,
		private ConfigService $configService,
	) {
		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param int $interface
	 */
	public function setCurrentInterface(int $interface): void {
		$this->currentInterface = $interface;
	}

	/**
	 * @return int
	 * @throws UnknownInterfaceException
	 */
	public function getCurrentInterface(): int {
		if ($this->currentInterface === 0) {
			throw new UnknownInterfaceException('interface not initialized');
		}

		return $this->currentInterface;
	}

	/**
	 * @return bool
	 */
	public function hasCurrentInterface(): bool {
		return ($this->currentInterface !== 0);
	}


	/**
	 * @return bool
	 * @throws UnknownInterfaceException
	 */
	public function isCurrentInterfaceInternal(): bool {
		return $this->isInterfaceInternal($this->getCurrentInterface());
	}

	/**
	 * @param int $interface
	 *
	 * @return bool
	 */
	public function isInterfaceInternal(int $interface): bool {
		if ($interface === self::IFACE_INTERNAL) {
			return true;
		}
		return match ($interface) {
			self::IFACE0 => $this->configService->getAppValueBool(ConfigService::IFACE0_INTERNAL),
			self::IFACE1 => $this->configService->getAppValueBool(ConfigService::IFACE1_INTERNAL),
			self::IFACE2 => $this->configService->getAppValueBool(ConfigService::IFACE2_INTERNAL),
			self::IFACE3 => $this->configService->getAppValueBool(ConfigService::IFACE3_INTERNAL),
			self::IFACE4 => $this->configService->getAppValueBool(ConfigService::IFACE4_INTERNAL),
			default => false,
		};
	}


	/**
	 * @param IRequest $request
	 * @param string $testToken
	 */
	public function setCurrentInterfaceFromRequest(IRequest $request, string $testToken = ''): void {
		$testing = [
			self::IFACE_INTERNAL => $this->configService->getInternalInstance(),
			self::IFACE_FRONTAL => $this->configService->getFrontalInstance(),
			self::IFACE0 => $this->configService->getIfaceInstance(self::IFACE0),
			self::IFACE1 => $this->configService->getIfaceInstance(self::IFACE1),
			self::IFACE2 => $this->configService->getIfaceInstance(self::IFACE2),
			self::IFACE3 => $this->configService->getIfaceInstance(self::IFACE3),
			self::IFACE4 => $this->configService->getIfaceInstance(self::IFACE4),
		];

		if ($testToken !== ''
			&& $testToken === $this->configService->getAppValue(ConfigService::IFACE_TEST_TOKEN)) {
			$testing[self::IFACE_TEST] = $this->getTestingInstance();
		}

		$serverHost = strtolower($request->getServerHost());
		if ($serverHost === '') {
			return;
		}

		foreach ($testing as $iface => $instance) {
			if ($serverHost === strtolower($instance)) {
				$this->setCurrentInterface($iface);

				return;
			}
		}
	}


	/**
	 * @param string $instance
	 *
	 * @return int
	 * @throws RemoteNotFoundException
	 */
	public function getInterfaceFromInstance(string $instance): int {
		$remoteInstance = $this->remoteRequest->getFromInstance($instance);

		return $remoteInstance->getInterface();
	}

	/**
	 *
	 */
	public function setCurrentInterfaceFromInstance(string $instance): void {
		try {
			$this->setCurrentInterface($this->getInterfaceFromInstance($instance));
		} catch (RemoteNotFoundException) {
		}
	}


	/**
	 * @param int $interface
	 *
	 * @return bool
	 */
	public function isInterfaceConfigured(int $interface): bool {
		try {
			$config = $this->getCloudIdConfigKey($interface);
		} catch (UnknownInterfaceException) {
			return false;
		}

		return ($this->configService->getAppValue($config) !== '');
	}


	/**
	 * @param int $interface
	 *
	 * @return string
	 * @throws UnknownInterfaceException
	 */
	private function getCloudIdConfigKey(int $interface): string {
		return match ($interface) {
			self::IFACE_INTERNAL => ConfigService::INTERNAL_CLOUD_ID,
			self::IFACE_FRONTAL => ConfigService::FRONTAL_CLOUD_ID,
			self::IFACE0 => ConfigService::IFACE0_CLOUD_ID,
			self::IFACE1 => ConfigService::IFACE1_CLOUD_ID,
			self::IFACE2 => ConfigService::IFACE2_CLOUD_ID,
			self::IFACE3 => ConfigService::IFACE3_CLOUD_ID,
			self::IFACE4 => ConfigService::IFACE4_CLOUD_ID,
			default => throw new UnknownInterfaceException('unknown interface'),
		};
	}


	/**
	 * @param bool $useString
	 *
	 * @return array
	 */
	public function getInterfaces(bool $useString = false): array {
		$interfaces = [
			self::IFACE_INTERNAL => $this->configService->getInternalInstance(),
			self::IFACE_FRONTAL => $this->configService->getFrontalInstance(),
			self::IFACE0 => $this->configService->getIfaceInstance(InterfaceService::IFACE0),
			self::IFACE1 => $this->configService->getIfaceInstance(InterfaceService::IFACE1),
			self::IFACE2 => $this->configService->getIfaceInstance(InterfaceService::IFACE2),
			self::IFACE3 => $this->configService->getIfaceInstance(InterfaceService::IFACE3),
			self::IFACE4 => $this->configService->getIfaceInstance(InterfaceService::IFACE4)
		];

		if (!$useString) {
			return $interfaces;
		}

		$detailed = [];
		foreach ($interfaces as $id => $iface) {
			$detailed[self::LIST_IFACE[$id]] = $iface;
		}

		return $detailed;
	}


	/**
	 * @param bool $useString
	 *
	 * @return array
	 */
	public function getInternalInterfaces(bool $useString = false): array {
		$interfaces = $this->getInterfaces(false);
		$internalInterfaces = [];

		foreach ($interfaces as $id => $iface) {
			if (!$this->isInterfaceInternal($id)) {
				continue;
			}

			$internalInterfaces[$id] = $iface;
		}

		if (!$useString) {
			return $internalInterfaces;
		}

		$detailed = [];
		foreach ($internalInterfaces as $id => $iface) {
			$detailed[self::LIST_IFACE[$id]] = $iface;
		}

		return $detailed;
	}


	/**
	 * use this only if interface must be defined. If not, use getLocalInstance()
	 *
	 * @throws UnknownInterfaceException
	 */
	public function getCloudInstance(int $interface = 0): string {
		if ($interface === 0) {
			$interface = $this->getCurrentInterface();
		}
		return match ($interface) {
			self::IFACE_INTERNAL => $this->configService->getInternalInstance(),
			self::IFACE_FRONTAL => $this->configService->getFrontalInstance(),
			self::IFACE0, self::IFACE1, self::IFACE2, self::IFACE3, self::IFACE4 => $this->configService->getIfaceInstance($interface),
			self::IFACE_TEST => $this->getTestingInstance(),
			default => throw new UnknownInterfaceException('unknown configured interface'),
		};
	}


	/**
	 * @throws UnknownInterfaceException
	 */
	public function getCloudPath(string $route = '', array $args = [], int $interface = 0): string {
		if ($interface === 0) {
			$interface = $this->getCurrentInterface();
		}

		$scheme = $path = '';
		switch ($interface) {
			case self::IFACE_INTERNAL:
				$scheme = $this->configService->getAppValue(ConfigService::INTERNAL_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::INTERNAL_CLOUD_PATH);
				break;
			case self::IFACE_FRONTAL:
				$scheme = $this->configService->getAppValue(ConfigService::FRONTAL_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::FRONTAL_CLOUD_PATH);
				break;
			case self::IFACE0:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE0_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE0_CLOUD_PATH);
				break;
			case self::IFACE1:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE1_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE1_CLOUD_PATH);
				break;
			case self::IFACE2:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE2_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE2_CLOUD_PATH);
				break;
			case self::IFACE3:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE3_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE3_CLOUD_PATH);
				break;
			case self::IFACE4:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE4_CLOUD_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE4_CLOUD_PATH);
				break;
			case self::IFACE_TEST:
				$scheme = $this->configService->getAppValue(ConfigService::IFACE_TEST_SCHEME);
				$path = $this->configService->getAppValue(ConfigService::IFACE_TEST_PATH);
				break;
		}

		if ($scheme === '') {
			throw new UnknownInterfaceException('misconfigured scheme');
		}

		$base = $scheme . '://' . $this->getCloudInstance($interface) . $path;
		if ($route === '') {
			return $base;
		}

		return $base . $this->configService->linkToRoute($route, $args);
	}


	/**
	 * should be used when unsure about the used Interface
	 *
	 * @return string
	 */
	public function getLocalInstance(): string {
		if ($this->hasCurrentInterface()) {
			try {
				return $this->getCloudInstance();
			} catch (UnknownInterfaceException) {
			}
		}

		return $this->configService->getLoopbackInstance();
	}


	/**
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function getLocalPath(string $route, array $args): string {
		$base = $this->configService->getAppValue(ConfigService::FRONTAL_CLOUD_BASE);
		if ($base === '') {
			return $this->configService->getLoopbackPath($route, $args);
		}

		return rtrim($base, '/') . $this->configService->linkToRoute($route, $args);
	}


	/**
	 * should be used when trying to generate an address
	 *
	 * @param string $route
	 * @param array $args
	 *
	 * @return string
	 */
	public function getFrontalPath(string $route, array $args): string {
		$frontalBase = $this->configService->getAppValue(ConfigService::FRONTAL_CLOUD_BASE);
		if ($frontalBase !== '') {
			return $this->getLocalPath($route, $args);
		}

		if ($this->isInterfaceConfigured(self::IFACE_FRONTAL)) {
			try {
				return $this->getCloudPath($route, $args, self::IFACE_FRONTAL);
			} catch (UnknownInterfaceException) {
			}
		}

		$ifaces = [self::IFACE0, self::IFACE1, self::IFACE2, self::IFACE3, self::IFACE4];
		foreach ($ifaces as $iface) {
			if ($this->isInterfaceConfigured($iface) && !$this->isInterfaceInternal($iface)) {
				try {
					return $this->getCloudPath($route, $args, $iface);
				} catch (UnknownInterfaceException) {
				}
			}
		}

		if ($this->isInterfaceConfigured(self::IFACE_INTERNAL)) {
			try {
				return $this->getCloudPath($route, $args, self::IFACE_INTERNAL);
			} catch (UnknownInterfaceException) {
			}
		}

		foreach ($ifaces as $iface) {
			if ($this->isInterfaceConfigured($iface) && $this->isInterfaceInternal($iface)) {
				try {
					return $this->getCloudPath($route, $args, $iface);
				} catch (UnknownInterfaceException) {
				}
			}
		}

		try {
			return $this->getCloudPath($route, $args);
		} catch (UnknownInterfaceException) {
		}

		return $this->getLocalPath($route, $args);
	}


	/**
	 * @return string
	 */
	private function getTestingInstance(): string {
		return $this->configService->getAppValue(ConfigService::IFACE_TEST_ID);
	}
}
