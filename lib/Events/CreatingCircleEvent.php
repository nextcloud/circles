<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class CreatingCircleEvent
 *
 * This event is called when a new Circle is created.
 * This event is called on every targeted instance of Nextcloud.
 * Targeted instance for this event are usually the one configured as GlobalScale.
 *
 * The entry is already generated in the circles table.
 * The owner is already generated in the members table.
 *
 * This is a good place if anything needs to be executed when a new Circle has been created.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleCreatedEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class CreatingCircleEvent extends CircleGenericEvent {
	/**
	 * CreatingCircleEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
