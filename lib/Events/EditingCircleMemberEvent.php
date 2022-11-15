<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
