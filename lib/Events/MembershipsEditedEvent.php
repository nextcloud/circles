<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Membership;
use OCP\EventDispatcher\Event;

/**
 * Class MembershipsEditedEvent
 *
 * @package OCA\Circles\Events
 */
class MembershipsEditedEvent extends Event {
	/** @var Membership[] */
	private $memberships;


	/**
	 * MembershipsEditedEvent constructor.
	 *
	 * @param Membership[] $memberships
	 */
	public function __construct(array $memberships) {
		parent::__construct();

		$this->memberships = $memberships;
	}


	/**
	 * @return Membership[]
	 */
	public function getMemberships(): array {
		return $this->memberships;
	}
}
