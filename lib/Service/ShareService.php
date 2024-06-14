<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Service;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class ShareService
 *
 * @package OCA\Circles\Service
 */
class ShareService {
	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * ShareService constructor.
	 *
	 * @param FederatedEventService $federatedEventService
	 * @param ConfigService $configService
	 */
	public function __construct(FederatedEventService $federatedEventService, ConfigService $configService) {
		$this->federatedEventService = $federatedEventService;
		$this->configService = $configService;
	}


	/**
	 * @param Circle $circle
	 */
	public function syncRemoteShares(Circle $circle) {
		//		$event = new FederatedEvent(SharedItemsSync::class);
		//		$event->setCircle($circle);
		//		$this->federatedEventService->newEvent($event);
	}
}
