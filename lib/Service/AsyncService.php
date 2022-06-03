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

use OC;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Exceptions\InternalAsyncException;
use OCA\Circles\IInternalAsync;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\EventWrapper;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TAsync;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TStringTools;
use ReflectionClass;
use ReflectionException;

class AsyncService {
	use TAsync;
	use TStringTools;
	use TNCRequest;

	private EventWrapperRequest $eventWrapperRequest;
	private ConfigService $configService;

	private bool $asynced = false;
	private bool $splittable = false;

	public function __construct(
		EventWrapperRequest $eventWrapperRequest,
		ConfigService $configService
	) {
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->configService = $configService;
	}


	/**
	 * split the process, anything after calling this method is out of main process.
	 * Will only work if isSplittable() is true.
	 */
	public function split(string $reason = ''): void {
		if (!$this->isSplittable()) {
			return;
		}

		$this->async($reason);
		$this->setAsynced();
	}

	public function splitArray(array $array): void {
		$this->split(json_encode($array));
	}


	public function isSplittable(): bool {
		return $this->splittable;
	}

	public function setSplittable(bool $splittable): void {
		$this->splittable = $splittable;
	}

	/**
	 * call this method only from WrappedEventController to confirm further process that
	 * we are already not running on a main process.
	 *
	 * @param bool $asynced
	 */
	public function setAsynced(bool $asynced = true): void {
		$this->asynced = $asynced;
	}

	public function isAsynced(): bool {
		return $this->asynced;
	}


	/**
	 * @throws RequestNetworkException
	 */
	public function asyncBroadcast(FederatedEvent $event, array $instances) {
		if (empty($instances) && !$event->isAsync()) {
			return;
		}

		$wrapper = new EventWrapper(EventWrapper::TYPE_BROADCAST);
		$wrapper->setEvent($event);
		$wrapper->setToken($this->uuid());
		$wrapper->setCreation(time());
		$wrapper->setSeverity($event->getSeverity());

		if ($event->isAsync()) {
			$wrapper->setInstance($this->configService->getLoopbackInstance());
			$this->eventWrapperRequest->save($wrapper);
		}

		foreach ($instances as $instance) {
			if ($event->getCircle()->isConfig(Circle::CFG_LOCAL)) {
				break;
			}

			$wrapper->setInstance($instance->getInstance());
			$wrapper->setInterface($instance->getInterface());
			// TODO: implement single save of multiple wrappers to avoid 10+ queries on big circles with
			// a lot of instances
			$this->eventWrapperRequest->save($wrapper);
		}

		$event->setWrapperToken($wrapper->getToken());

		if ($this->isAsynced()) {
			// we're not on main process, we run the broadcast on this thread.
			// Also cannot add EventWrapperService to DI or loop.
			/** @var EventWrapperService $eventWrapperService */
			$eventWrapperService = \OC::$server->get(EventWrapperService::class);
			$eventWrapperService->performBroadcast($wrapper->getToken());

			return;
		}

		try {
			$request = new NCRequest('', Request::TYPE_POST);
			$this->configService->configureLoopbackRequest(
				$request,
				'circles.EventWrapper.asyncBroadcast',
				['token' => $wrapper->getToken()]
			);

			$this->doRequest($request);
		} catch (RequestNetworkException $e) {
			$this->e($e, ['wrapper' => $wrapper]);
		}
	}


	/**
	 * @param string $internalAsync
	 * @param ReferencedDataStore|null $store
	 */
	public function asyncInternal(string $internalAsync, ?ReferencedDataStore $store = null): void {
		if (is_null($store)) {
			$store = new ReferencedDataStore();
		}

		$store->s(IInternalAsync::STORE_INTERNAL_ASYNC, $internalAsync);

		$wrapper = new EventWrapper(EventWrapper::TYPE_INTERNAL);
		$wrapper->setStore($store);
		$wrapper->setToken($this->uuid());
		$wrapper->setCreation(time());

		$this->eventWrapperRequest->save($wrapper);

		if ($this->isAsynced()) {
			// we're not on main process, we run it on this thread.
			// Also cannot add EventWrapperService to DI or loop.

			/** @var EventWrapperService $eventWrapperService */
			$eventWrapperService = \OC::$server->get(EventWrapperService::class);
			$eventWrapperService->performInternal($wrapper->getToken());

			return;
		}

		$request = new NCRequest('', Request::TYPE_POST);
		$this->configService->configureLoopbackRequest(
			$request,
			'circles.EventWrapper.asyncInternal',
			['token' => $wrapper->getToken()]
		);

		try {
			$this->doRequest($request);
		} catch (RequestNetworkException $e) {
			$this->e($e, ['wrapper' => $wrapper]);
		}
	}


	/**
	 * @param EventWrapper $wrapper
	 *
	 * @throws InternalAsyncException
	 * @throws InvalidItemException
	 */
	public function runInternalAsync(EventWrapper $wrapper): void {
		$store = $wrapper->getStore();
		$internalAsync = $this->getInternalAsync($store);

		$store->u(IInternalAsync::STORE_INTERNAL_ASYNC);
		$internalAsync->runAsynced($store);
	}


	/**
	 * @param ReferencedDataStore $store
	 *
	 * @return IInternalAsync
	 * @throws InvalidItemException
	 * @throws InternalAsyncException
	 */
	private function getInternalAsync(ReferencedDataStore $store): IInternalAsync {
		$class = $store->g(IInternalAsync::STORE_INTERNAL_ASYNC);

		try {
			$test = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new InternalAsyncException('ReflectionException with ' . $class . ': ' . $e->getMessage());
		}

		if (!in_array(IInternalAsync::class, $test->getInterfaceNames())) {
			throw new InternalAsyncException($class . ' does not implements IInternalAsync');
		}

		$item = OC::$server->get($class);
		if (!($item instanceof IInternalAsync)) {
			throw new InternalAsyncException($class . ' not an IInternalAsync');
		}

		return $item;
	}

}
