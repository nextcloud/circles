<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class RequestingCircleMemberEvent
 *
 * This event is called when one or multiple members are requesting/invited to a Circle.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The entry is already generated in the members table.
 *
 * This is a good place if anything needs to be executed when a member requests or is invited to a Circle.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleMemberRequestedEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class RequestingCircleMemberEvent extends CircleMemberGenericEvent {
	/** @var int */
	private $type = 0;


	/**
	 * RequestingCircleMemberEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}


	/**
	 * @param int $type
	 *
	 * @return $this
	 */
	public function setType(int $type): self {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}
}
