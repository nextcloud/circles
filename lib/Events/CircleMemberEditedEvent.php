<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class CircleMemberEditedEvent
 *
 * This Event is called when it has been confirmed that a Member have been edited on all instances used
 * by the Circle.
 * Meaning that the event won't be triggered until each instances have been once available during the
 * retry-on-fail initiated in a background job
 *
 * WARNING: Unlike EditingCircleMemberEvent, this Event is only called on the master instance of the Circle.
 *
 * @package OCA\Circles\Events
 */
class CircleMemberEditedEvent extends CircleResultGenericEvent {
	/** @var int */
	private $type = 0;

	/** @var int */
	private $newLevel = 0;

	/** @var string */
	private $newDisplayName = '';


	/**
	 * CircleMemberEditedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function __construct(FederatedEvent $federatedEvent, array $results) {
		parent::__construct($federatedEvent, $results);
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
	 * @param int $newLevel
	 *
	 * @return CircleMemberEditedEvent
	 */
	public function setNewLevel(int $newLevel): self {
		$this->newLevel = $newLevel;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getNewLevel(): int {
		return $this->newLevel;
	}


	/**
	 * @param string $newDisplayName
	 *
	 * @return CircleMemberEditedEvent
	 */
	public function setNewDisplayName(string $newDisplayName): self {
		$this->newDisplayName = $newDisplayName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNewDisplayName(): string {
		return $this->newDisplayName;
	}
}
