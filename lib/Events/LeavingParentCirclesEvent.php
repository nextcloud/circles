<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class LeavingParentCirclesEvent
 *
 * This event is called when a Circle is removed from all parent Circles.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The circle member entries have already been removed from parent circles in the members table.
 * The circle membership entries have already been removed from parent circles in the membership table.
 *
 * This is a good place if anything needs to be executed when a Circle has been removed from its parent Circles.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. LeftParentCirclesEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class LeavingParentCirclesEvent extends CircleGenericEvent {
	/**
	 * LeavingParentCirclesEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
