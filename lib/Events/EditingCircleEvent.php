<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class EditingCircleEvent
 *
 * This event is called when a circle is edited.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The entry is already edited in the circles table.
 * If needed, the entries in the memberships table are already edited.
 *
 * This is a good place if anything needs to be executed when a circle is edited.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleEditedEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class EditingCircleEvent extends CircleGenericEvent {
	/**
	 * EditingCircleEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
