<?php


declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events\Files;

use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Mount;

/**
 * Class PreparingFileShareEvent
 *
 * @package OCA\Circles\Events\Files
 */
class PreparingFileShareEvent extends CircleGenericEvent {
	/** @var Mount */
	private $mount;


	/**
	 * PreparingFileShareEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct($federatedEvent);
	}
}
