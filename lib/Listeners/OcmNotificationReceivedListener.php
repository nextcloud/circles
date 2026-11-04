<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\ISenderDomainAwareCloudFederationNotification;
use OCP\IRequest;
use OCP\OCM\Events\OCMNotificationReceivedEvent;

/**
 * Handles Circles events received on the OCM notifications endpoint.
 *
 * The result sent back to the sender contains either the 'result' of the event,
 * or the serialized 'exception' thrown while processing it.
 *
 * @template-implements IEventListener<OCMNotificationReceivedEvent>
 */
class OcmNotificationReceivedListener implements IEventListener {
	use TDeserialize;
	use TNCLogger;

	public function __construct(
		private readonly IRequest $request,
		private readonly IAppConfig $appConfig,
		private readonly InterfaceService $interfaceService,
		private readonly ConfigService $configService,
		private readonly RemoteDownstreamService $remoteDownstreamService,
		private readonly RemoteStreamService $remoteStreamService,
	) {
		$this->setup('app', Application::APP_ID);
	}

	/**
	 * @throws AuthenticationFailedException
	 */
	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof OCMNotificationReceivedEvent) {
			return;
		}

		$notification = $event->getNotification();
		if (
			!$notification instanceof ISenderDomainAwareCloudFederationNotification
			|| $notification->getSenderDomain() === null
			|| $notification->getResourceType() !== Application::OCM_RESOURCE_TYPE
			|| !in_array($notification->getNotificationType(), Application::OCM_NOTIFICATION_TYPES, true)
		) {
			return;
		}

		$senderDomain = $notification->getSenderDomain();
		try {
			$this->confirmEventInterface();
			$this->confirmRemoteInstance($senderDomain);
			$data = $notification->getNotification()['event'] ?? [];
			$federatedEvent = new FederatedEvent();
			$federatedEvent->import(is_array($data) ? $data : []);
			$federatedEvent->setSender($senderDomain);
		} catch (Exception $e) {
			$this->e($e);
			throw new AuthenticationFailedException();
		}

		$notificationType = $notification->getNotificationType();
		try {
			if ($notificationType === Application::OCM_NOTIFICATION_BROADCAST_EVENT) {
				$this->remoteDownstreamService->incomingEvent($federatedEvent);
				$event->setResult(['result' => $this->serialize($federatedEvent->getResult())]);
			} elseif ($notificationType === Application::OCM_NOTIFICATION_REQUEST_EVENT) {
				$this->remoteDownstreamService->requestedEvent($federatedEvent);
				$event->setResult(['result' => $federatedEvent->getOutcome()]);
			}
		} catch (FederatedItemException $e) {
			$event->setResult(['exception' => $e->jsonSerialize()]);
		} catch (Exception $e) {
			$this->e($e, ['event' => $federatedEvent->getWrapperToken()]);

			$event->setResult(['exception' => ['message' => $e->getMessage(), 'code' => $e->getCode()]]);
		}
	}

	/**
	 * @throws FederatedEventException
	 * @throws UnknownInterfaceException
	 */
	private function confirmEventInterface(): void {
		$this->interfaceService->setCurrentInterfaceFromRequest($this->request);
		$iface = $this->interfaceService->getCurrentInterface();
		if ($iface === InterfaceService::IFACE_FRONTAL
			&& !$this->appConfig->getAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED)) {
			throw new FederatedEventException('frontal interface is not enabled');
		}
	}

	/**
	 * Confirms that the sender is a known and trusted remote instance,
	 * and sets the current interface to the one used to reach it.
	 *
	 * @throws RemoteNotFoundException
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(string $senderDomain): void {
		$remoteInstance = $this->remoteStreamService->getRemoteInstanceFromAddress($senderDomain);
		if (!$this->configService->isLocalInstance($senderDomain)
			&& $remoteInstance->getType() === RemoteInstance::TYPE_UNKNOWN) {
			throw new SignatoryException('Could not confirm identity');
		}

		$this->interfaceService->setCurrentInterface($remoteInstance->getInterface());
	}
}
