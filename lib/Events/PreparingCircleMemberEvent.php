<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class PreparingCircleMemberEvent
 *
 * This event is called when one or multiple members are added to a Circle.
 *
 * This event is called on the master instance of the circle, before AddingCircleMemberEvent.
 *
 * @package OCA\Circles\Events
 */
class PreparingCircleMemberEvent extends CircleMemberGenericEvent {
	/**
	 * PreparingCircleMemberEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
