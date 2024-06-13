<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMembership;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\CircleEventService;
use OCA\Circles\Tools\Model\SimpleDataStore;

/**
 * Class SharesSync
 *
 * @package OCA\Circles\FederatedItems
 */
class SharedItemsSync implements
	IFederatedItem,
	IFederatedItemLimitedToInstanceWithMembership {
	// TODO: testing that IFederatedItemLimitedToInstanceWithMembership is working (since multi-instance)
	// TODO: implements IFederatedItemInstanceMember to the check procedure

	/** @var CircleEventService */
	private $circleEventService;


	public function __construct(CircleEventService $circleEventService) {
		$this->circleEventService = $circleEventService;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$this->circleEventService->onSharedItemsSyncRequested($event);

		$event->setResult(new SimpleDataStore(['shares' => 'ok']));
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
