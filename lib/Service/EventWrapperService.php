<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Db\EventWrapperRequest;
use OCA\Circles\Model\Federated\EventWrapper;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class EventWrapperService
 *
 * @package OCA\Circles\Service
 */
class EventWrapperService extends NCSignature {
	use TNCRequest;
	use TStringTools;


	public const RETRY_ASAP = 'asap';
	public const RETRY_HOURLY = 'hourly';
	public const RETRY_DAILY = 'daily';
	public const RETRY_ERROR = 100;
	public static $RETRIES = [
		'asap' => [0, 5],
		'hourly' => [5, 150],
		'daily' => [150, 300]
	];


	/** @var EventWrapperRequest */
	private $eventWrapperRequest;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

	/** @var ConfigService */
	private $configService;


	/**
	 * EventWrapperService constructor.
	 *
	 * @param EventWrapperRequest $eventWrapperRequest
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		EventWrapperRequest $eventWrapperRequest,
		FederatedEventService $federatedEventService,
		RemoteUpstreamService $remoteUpstreamService,
		ConfigService $configService,
	) {
		$this->eventWrapperRequest = $eventWrapperRequest;
		$this->federatedEventService = $federatedEventService;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->configService = $configService;
	}


	/**
	 * @param string $token
	 * @param bool $refresh
	 */
	public function confirmStatus(string $token, bool $refresh = false): void {
		$wrappers = $this->eventWrapperRequest->getByToken($token);

		foreach ($wrappers as $wrapper) {
			$status = $wrapper->getStatus();
			if ($refresh && ($status === EventWrapper::STATUS_FAILED ||
							 $status === EventWrapper::STATUS_INIT)) {
				$wrapper->setStatus(EventWrapper::STATUS_INIT);
				$this->eventWrapperRequest->update($wrapper);
				$status = $this->manageWrapper($wrapper);
			}

			if ($status !== EventWrapper::STATUS_DONE) {
				return;
			}
		}

		$this->federatedEventService->manageResults($token);
		$this->eventWrapperRequest->updateAll($token, EventWrapper::STATUS_OVER);
	}


	/**
	 * @param EventWrapper $wrapper
	 *
	 * @return int
	 */
	public function manageWrapper(EventWrapper $wrapper): int {
		if ($wrapper->getStatus() !== EventWrapper::STATUS_INIT) {
			return $wrapper->getStatus();
		}

		$status = EventWrapper::STATUS_FAILED;
		$retry = $wrapper->getRetry();
		try {
			if ($this->configService->isLocalInstance($wrapper->getInstance())) {
				$gs = $this->federatedEventService->getFederatedItem($wrapper->getEvent(), false);
				$gs->manage($wrapper->getEvent());
			} else {
				$this->remoteUpstreamService->broadcastEvent($wrapper);
			}
			$status = EventWrapper::STATUS_DONE;
		} catch (Exception $e) {
			$retry++;
		}

		if ($wrapper->getSeverity() !== FederatedEvent::SEVERITY_HIGH) {
			$status = EventWrapper::STATUS_OVER;
		}

		$wrapper->setStatus($status);
		$wrapper->setRetry($retry);
		$wrapper->setResult($wrapper->getEvent()->getResult());

		$this->eventWrapperRequest->update($wrapper);

		return $status;
	}


	/**
	 * @param string $retry
	 */
	public function retry(string $retry) {
		$tokens = $this->getFailedEvents(self::$RETRIES[$retry]);
		foreach ($tokens as $token) {
			$this->confirmStatus($token, true);
		}
	}


	/**
	 * @param array $retryRange
	 *
	 * @return array
	 */
	private function getFailedEvents(array $retryRange): array {
		$token = array_map(
			function (EventWrapper $event): string {
				return $event->getToken();
			}, $this->eventWrapperRequest->getFailedEvents($retryRange)
		);

		return array_values(array_unique($token));
	}
}
