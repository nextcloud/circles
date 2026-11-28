<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\AuditLog\Operation;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\OCM\Events\OCMEndpointRequestEvent;

/**
 * Handles circles requests received on the OCM endpoint (ocm/circles/<item>).
 *
 * @template-implements IEventListener<OCMEndpointRequestEvent>
 */
class OcmEndpointRequestListener implements IEventListener {
	use TDeserialize;
	use TNCLogger;

	public function __construct(
		private readonly IRequest $request,
		private readonly IAppConfig $appConfig,
		private readonly InterfaceService $interfaceService,
		private readonly ConfigService $configService,
		private readonly RemoteDownstreamService $remoteDownstreamService,
		private readonly RemoteStreamService $remoteStreamService,
		private readonly AuditLogService $auditLogService,
	) {
		$this->setup('app', Application::APP_ID);
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof OCMEndpointRequestEvent
			|| $event->getRequestedCapability() !== Application::OCM_CAPABILITY
			|| $event->getUsedMethod() !== 'POST') {
			return;
		}

		$args = $event->getArgs();
		if (count($args) !== 1 || !in_array($args[0], Application::OCM_SUPPORTED_ITEMS, true)) {
			return;
		}

		try {
			$federatedEvent = $this->extractEvent($event);
		} catch (Exception $e) {
			$this->e($e);
			$event->setResponse($this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED));
			return;
		}

		try {
			if ($args[0] === RemoteInstance::INCOMING) {
				// RemoteInstance::INCOMING: sent by the server hosting the team to all remote servers that have members
				$this->remoteDownstreamService->incomingEvent($federatedEvent);
				$response = new DataResponse($this->serialize($federatedEvent->getResult()));
			} else {
				// RemoteInstance::EVENT: sent by a server with team members to the server hosting the team
				$this->remoteDownstreamService->requestedEvent($federatedEvent);
				$response = new DataResponse($federatedEvent->getOutcome());
			}
		} catch (Exception $e) {
			$this->e($e, ['event' => $federatedEvent->getWrapperToken()]);
			$response = $this->exceptionResponse($e);
		}

		$event->setResponse($response);
	}

	/**
	 * @throws FederatedEventException
	 * @throws InvalidItemException
	 * @throws RemoteNotFoundException
	 * @throws SignatoryException
	 * @throws UnknownInterfaceException
	 */
	private function extractEvent(OCMEndpointRequestEvent $event): FederatedEvent {
		$this->interfaceService->setCurrentInterfaceFromRequest($this->request);
		if ($this->interfaceService->getCurrentInterface() === InterfaceService::IFACE_FRONTAL
			&& !$this->appConfig->getAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED)) {
			throw new FederatedEventException('frontal interface is not enabled');
		}

		$origin = $event->getRemote();
		if ($origin === null) {
			throw new SignatoryException('Request is not signed');
		}
		$this->confirmRemoteInstance($origin);

		$federatedEvent = new FederatedEvent();
		$federatedEvent->import($event->getPayload());
		$federatedEvent->setSender($origin);

		return $federatedEvent;
	}

	/**
	 * Confirms that the signed origin is a known and trusted remote instance.
	 *
	 * @throws RemoteNotFoundException
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(string $origin): void {
		$remoteInstance = $this->remoteStreamService->getRemoteInstanceFromAddress($origin);
		if (!$this->configService->isLocalInstance($origin)
			&& $remoteInstance->getType() === RemoteInstance::TYPE_UNKNOWN) {
			$this->auditLogService->log(Operation::RemoteRequestRejected, 'Federated request from instance "%s" rejected: %s', [
				'instance' => $origin,
				'reason' => 'untrusted_instance',
			]);
			throw new SignatoryException('Could not confirm identity');
		}

		$this->interfaceService->setCurrentInterface($remoteInstance->getInterface());
	}

	private function exceptionResponse(Exception $e, int $httpErrorCode = Http::STATUS_BAD_REQUEST): DataResponse {
		if ($e instanceof FederatedItemException) {
			return new DataResponse($this->serialize($e), $e->getStatus());
		}

		$errorCode = (int)$e->getCode();
		return new DataResponse(
			[
				'message' => $e->getMessage(),
				'code' => $e->getCode(),
			],
			($errorCode > 0) ? $errorCode : $httpErrorCode
		);
	}
}
