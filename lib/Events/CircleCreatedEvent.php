<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class CircleCreatedEvent
 *
 * This Event is called when it has been confirmed that the Circle have been created on all targeted
 * instances.
 * Targeted instance for this event are usually the one configured as GlobalScale.
 *
 * Meaning that the event won't be triggered until each instances have been once available during the
 * retry-on-fail initiated in a background job
 *
 * WARNING: Unlike CreatingCircleEvent, this Event is only called on the master instance of the Circle.
 *
 * @package OCA\Circles\Events
 */
class CircleCreatedEvent extends CircleResultGenericEvent {
	/**
	 * CircleCreatedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function __construct(FederatedEvent $federatedEvent, array $results) {
		parent::__construct($federatedEvent, $results);
	}
}
