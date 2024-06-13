<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class EditingCircleMemberEvent
 *
 * This event is called when a member is edited.
 * This event is called on every instance of Nextcloud related to the Circle.
 *
 * The entry is already edited in the members table.
 * If needed, the entries in the memberships table are already edited.
 *
 * This is a good place if anything needs to be executed when a member is edited.
 *
 * If anything needs to be managed on the master instance of the Circle (ie. CircleMemberEditedEvent), please
 * use:
 *    $event->getFederatedEvent()->setResultEntry(string $key, array $data);
 *
 * @package OCA\Circles\Events
 */
class EditingCircleMemberEvent extends CircleMemberGenericEvent {
	/** @var int */
	private $type = 0;

	/** @var int */
	private $level = 0;

	/** @var string */
	private $displayName = '';


	/**
	 * EditingCircleMemberEvent constructor.
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


	/**
	 * @param int $level
	 *
	 * @return EditingCircleMemberEvent
	 */
	public function setLevel(int $level): self {
		$this->level = $level;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevel(): int {
		return $this->level;
	}


	/**
	 * @param string $displayName
	 *
	 * @return EditingCircleMemberEvent
	 */
	public function setDisplayName(string $displayName): self {
		$this->displayName = $displayName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}
}
