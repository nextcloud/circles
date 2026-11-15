<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\OCM;

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
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ISenderDomainAwareCloudFederationProvider;
use OCP\IRequest;

/**
 * Handles Circles events received on the OCM notifications endpoint.
 *
 * The result sent back to the sender contains either the 'result' of the event,
 * or the serialized 'exception' thrown while processing it.
 */
class CirclesFederationProvider implements ISenderDomainAwareCloudFederationProvider {
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

	#[\Override]
	public function getShareType(): string {
		return Application::OCM_RESOURCE_TYPE;
	}

	#[\Override]
	public function getSupportedShareTypes(): array {
		return [];
	}

	#[\Override]
	public function shareReceived(ICloudFederationShare $share): string {
		throw new ProviderCouldNotAddShareException('Circles does not share resources over OCM', '', Http::STATUS_NOT_IMPLEMENTED);
	}

	#[\Override]
	public function notificationReceived(string $notificationType, string $providerId, array $notification): array {
		throw new ActionNotSupportedException($notificationType);
	}

	/**
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 */
	#[\Override]
	public function senderDomainAwareNotificationReceived(string $notificationType, ?string $providerId, array $notification, string $senderDomain, ?string $shareType): array {
		if (!in_array($notificationType, Application::OCM_NOTIFICATION_TYPES, true)) {
			throw new ActionNotSupportedException($notificationType);
		}

		try {
			$this->confirmEventInterface();
			$this->confirmRemoteInstance($senderDomain);
			$data = $notification['event'] ?? [];
			$federatedEvent = new FederatedEvent();
			$federatedEvent->import(is_array($data) ? $data : []);
			$federatedEvent->setSender($senderDomain);
		} catch (Exception $e) {
			$this->e($e);
			throw new AuthenticationFailedException();
		}

		try {
			if ($notificationType === Application::OCM_NOTIFICATION_BROADCAST_EVENT) {
				// OCM_NOTIFICATION_BROADCAST_EVENT: sent by the server hosting the team to all remote servers that have members
				$this->remoteDownstreamService->incomingEvent($federatedEvent);
				$result = $this->serialize($federatedEvent->getResult());
			} else {
				// OCM_NOTIFICATION_REQUEST_EVENT: sent by a server with team members to the server hosting the team
				$this->remoteDownstreamService->requestedEvent($federatedEvent);
				$result = $federatedEvent->getOutcome();
			}
			return ['result' => $result];
		} catch (FederatedItemException $e) {
			return ['exception' => $e->jsonSerialize()];
		} catch (Exception $e) {
			$this->e($e, ['event' => $federatedEvent->getWrapperToken()]);

			return ['exception' => ['message' => $e->getMessage(), 'code' => $e->getCode()]];
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
