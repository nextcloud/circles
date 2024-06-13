<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class DestroyingCircleEvent
 *
 * This event is called when a Circle is destroyed.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The entry is already removed from the circles table.
 * The members are already removed from the members table.
 * The entries from the memberships table are already refreshed.
 *
 * This is a good place if anything needs to be executed when a Circle has been destroyed.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleDestroyedEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 * *
 * @package OCA\Circles\Events
 */
class DestroyingCircleEvent extends CircleGenericEvent {
	/**
	 * DestroyingCircleEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
