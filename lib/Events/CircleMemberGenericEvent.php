<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;

/**
 * Class CircleMemberAddedEvent
 *
 * @package OCA\Circles\Events
 */
class CircleMemberGenericEvent extends CircleGenericEvent {
	/** @var Member */
	private $member;


	/**
	 * CircleMemberAddedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);

		$this->member = $federatedEvent->getMember();
	}


	/**
	 * @return Member
	 */
	public function getMember(): Member {
		return $this->member;
	}
}
