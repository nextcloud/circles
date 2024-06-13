<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class RemovingCircleMemberEvent
 *
 * This event is called when a member is removed from a Circle.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The entry is already removed from the members table.
 * The memberships of the member are already removed from the memberships table.
 *
 * This is a good place if anything needs to be executed when a member have been removed from a Circle.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleMemberRemovedEvent), please use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class RemovingCircleMemberEvent extends CircleMemberGenericEvent {
	/** @var int */
	private $type = 0;


	/**
	 * RemovingCircleMemberEvent constructor.
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
