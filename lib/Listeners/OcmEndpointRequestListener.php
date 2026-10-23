<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\RemoteEndpointService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\OCM\Events\OCMEndpointRequestEvent;

/** @template-implements IEventListener<OCMEndpointRequestEvent> */
class OcmEndpointRequestListener implements IEventListener {
	use TNCLogger;

	public function __construct(
		private readonly IRequest $request,
		private readonly RemoteEndpointService $remoteEndpointService,
		private readonly RemoteStreamService $remoteStreamService,
	) {
		$this->setup('app', Application::APP_ID);
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof OCMEndpointRequestEvent
			|| $event->getRequestedCapability() !== Application::OCM_CAPABILITY) {
			return;
		}

		if ($event->getUsedMethod() !== 'POST') {
			$event->setResponse(new DataResponse([], Http::STATUS_METHOD_NOT_ALLOWED));
			return;
		}

		$args = $event->getArgs();
		$item = array_shift($args);
		$response = match ([$item, count($args)]) {
			[RemoteInstance::EVENT, 0] => $this->event($event, false),
			[RemoteInstance::INCOMING, 0] => $this->event($event, true),
			[RemoteInstance::CIRCLES, 0] => $this->dataRequest($event, fn (SimpleDataStore $data): array => $this->remoteEndpointService->getCircles($data)),
			[RemoteInstance::CIRCLE, 1] => $this->dataRequest($event, fn (): array => $this->remoteEndpointService->getCircle($args[0])),
			[RemoteInstance::MEMBERS, 1] => $this->dataRequest($event, fn (): array => $this->remoteEndpointService->getMembers($args[0])),
			[RemoteInstance::MEMBER, 2] => $this->dataRequest($event, fn (): array => $this->remoteEndpointService->getFederatedUser($args[0], $args[1])),
			[RemoteInstance::INHERITED, 1] => $this->dataRequest($event, fn (): array => $this->remoteEndpointService->getInheritedMembers($args[0])),
			[RemoteInstance::MEMBERSHIPS, 1] => $this->dataRequest($event, fn (): array => $this->remoteEndpointService->getMemberships($args[0])),
			default => new DataResponse([], Http::STATUS_NOT_FOUND),
		};

		$event->setResponse($response);
	}

	/**
	 * @param bool $incoming: true for an event broadcast by the instance owning the circle,
	 *                        false for an event requested by another instance
	 */
	private function event(OCMEndpointRequestEvent $event, bool $incoming): DataResponse {
		try {
			$this->remoteEndpointService->confirmEventInterface($this->request);
			$this->confirmRemoteInstance($event);
			$federatedEvent = $this->remoteEndpointService->importEvent($event->getPayload(), (string)$event->getRemote());
		} catch (Exception $e) {
			$this->e($e);

			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			if ($incoming) {
				return new DataResponse($this->remoteEndpointService->incomingEvent($federatedEvent));
			}

			return new DataResponse($this->remoteEndpointService->requestedEvent($federatedEvent));
		} catch (Exception $e) {
			$this->e($e, ['event' => $federatedEvent->getWrapperToken()]);

			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 * @param callable(SimpleDataStore): array $handler
	 */
	private function dataRequest(OCMEndpointRequestEvent $event, callable $handler): DataResponse {
		try {
			$remoteInstance = $this->confirmRemoteInstance($event);
			$data = $this->remoteEndpointService->prepareDataRequest($remoteInstance, $event->getPayload());
		} catch (Exception $e) {
			$this->e($e);

			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($handler($data));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(OCMEndpointRequestEvent $event): RemoteInstance {
		$origin = $event->getRemote();
		if ($origin === null) {
			throw new SignatoryException('Request is not signed');
		}

		try {
			$remoteInstance = $this->remoteStreamService->getRemoteInstanceFromAddress($origin);
		} catch (RemoteNotFoundException) {
			$remoteInstance = null;
		}

		return $this->remoteEndpointService->confirmRemoteInstance($remoteInstance, $origin);
	}
}
