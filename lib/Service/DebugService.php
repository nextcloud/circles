<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\Circles\Service;

use Exception;
use JsonSerializable;
use OCA\Circles\Db\DebugRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Debug;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TStringTools;
use Psr\Log\LoggerInterface;

class DebugService {
	use TStringTools;

	public const ACTION = '_action';
	public const EXCEPTION = '_exception';
	public const E_CLASS = '_class';
	public const E_TRACE = '_trace';

	const DEBUG_LOCAL = 'local';
	const DEBUG_DAEMON = 'daemon';


	private LoggerInterface $loggerInterface;
	private DebugRequest $debugRequest;
	private RemoteStreamService $remoteStreamService;
	private ConfigService $configService;

	private string $debugType;
	private string $processToken;

	/**
	 * @param DebugRequest $debugRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		LoggerInterface $loggerInterface,
		DebugRequest $debugRequest,
		RemoteStreamService $remoteStreamService,
		ConfigService $configService
	) {
		$this->loggerInterface = $loggerInterface;
		$this->debugRequest = $debugRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->configService = $configService;

		$this->processToken = $this->token(7);
		$this->debugType = '';
	}


	public function setDebugType(string $type): void {
		$this->debugType = $type;
	}

	/**
	 * @param int $history
	 *
	 * @return Debug[]
	 */
	public function getHistory(int $history): array {
		return $this->debugRequest->getHistory($history);
	}

	/**
	 * @param int $lastId
	 *
	 * @return Debug[]
	 */
	public function getSince(int $lastId): array {
		return $this->debugRequest->getSince($lastId);
	}


	/**
	 * @param string $action
	 * @param string $circleId
	 * @param array<string, int, bool, array, JsonSerializable, IReferencedObject> $objects
	 */
	public function info(string $action, string $circleId = '', array $objects = []): void {
		if (!$this->isDebugEnabled()) {
			return;
		}

		$store = new ReferencedDataStore();
		$store->s(self::ACTION, $action);

		try {
			$this->store($store, $circleId, $objects);
		} catch (Exception $e) {
			//$this->logger->
		}
	}


	/**
	 * @param Exception $e
	 * @param string $circleId
	 * @param array<string, int, bool, array, JsonSerializable, IReferencedObject> $objects
	 */
	public function exception(Exception $e, string $circleId = '', array $objects = []): void {
		if (!$this->isDebugEnabled()) {
			return;
		}

		$msg = $e->getMessage();
		$store = new ReferencedDataStore();
		$store->s(self::ACTION, '{?' . self::E_CLASS . '}' . (($msg !== '') ? ' (' . $msg . ')' : ''));
		$store->s(self::E_CLASS, get_class($e));
		$store->s(self::EXCEPTION, $e->getMessage());
		$store->sArray(self::E_TRACE, debug_backtrace());

		try {
			$this->store($store, $circleId, $objects);
		} catch (Exception $e) {
			//$this->logger->
		}
	}


	/**
	 * @param ReferencedDataStore $store
	 * @param string $circleId
	 * @param array $objects
	 *
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	private function store(ReferencedDataStore $store, string $circleId, array $objects = []): void {
		foreach ($objects as $k => $obj) {
			try {
				$store->sMixed((string)$k, $obj);
			} catch (InvalidItemException $e) {
			}
		}

		$debug = new Debug($store, $circleId, $this->processToken, $this->debugType);
		if ($this->isDebugLocal()) {
			$this->save($debug);

			return;
		}

		$this->remote($debug);
	}

	/**
	 * @param Debug $debug
	 */
	public function save(Debug $debug): void {
		$this->debugRequest->save($debug);
	}

	/**
	 * @param Debug $debug
	 *
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	private function remote(Debug $debug): void {
		$this->remoteStreamService->resultRequestRemoteInstance(
			$this->getDebugInstance(),
			RemoteInstance::DEBUG,
			Request::TYPE_POST,
			$debug,
		);
	}


	private function isDebugEnabled(): bool {
		return ($this->configService->getAppValue(ConfigService::DEBUG) !== '');
	}

	private function isDebugLocal(): bool {
		return ($this->configService->getAppValue(ConfigService::DEBUG) === self::DEBUG_LOCAL
				|| $this->configService->getAppValue(ConfigService::DEBUG) === self::DEBUG_DAEMON);
	}

	private function getDebugInstance(): string {
		return $this->configService->getAppValue(ConfigService::DEBUG);
	}

}
