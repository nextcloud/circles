<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems\Files;

use OCA\Circles\Db\MountRequest;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class FileUnshare
 *
 * @package OCA\Circles\FederatedItems
 */
class FileUnshare implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess,
	IFederatedItemMemberEmpty {
	use TStringTools;
	use TNCLogger;


	/** @var MountRequest */
	private $mountRequest;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * FileUnshare constructor.
	 *
	 * @param MountRequest $mountRequest
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MountRequest $mountRequest,
		EventService $eventService,
		ConfigService $configService,
	) {
		$this->mountRequest = $mountRequest;
		$this->eventService = $eventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		// TODO: check and improve
		// TODO: Could we use a share lock ?
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidItemException
	 * @throws UnknownTypeException
	 * @throws ItemNotFoundException
	 */
	public function manage(FederatedEvent $event): void {
		if ($this->configService->isLocalInstance($event->getOrigin())) {
			return;
		}

		/** @var ShareWrapper $wrappedShare */
		$wrappedShare = $event->getParams()->gObj('wrappedShare', ShareWrapper::class);

		$this->mountRequest->delete($wrappedShare->getToken());
		$this->eventService->federatedShareDeleted($wrappedShare);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
