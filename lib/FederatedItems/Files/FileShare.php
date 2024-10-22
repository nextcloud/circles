<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems\Files;

use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class FileShare
 *
 * @package OCA\Circles\FederatedItems\Files
 */
class FileShare implements
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
	 * FileShare constructor.
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
		// TODO: check (origin of file ?) and improve
		// TODO: Use a share lock

		$this->eventService->fileSharePreparing($event);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws UnknownTypeException
	 */
	public function manage(FederatedEvent $event): void {
		$mount = null;
		if (!$this->configService->isLocalInstance($event->getOrigin())) {
			/** @var ShareWrapper $wrappedShare */
			$wrappedShare = $event->getParams()->gObj('wrappedShare', ShareWrapper::class);

			$mount = new Mount();
			$mount->fromShare($wrappedShare);
			$mount->setMountId($this->token(15));

			$this->mountRequest->save($mount);
		}

		$this->eventService->fileShareCreating($event, $mount);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->fileShareCreated($event, $results);
	}
}
