<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Interface IFederatedItem
 *
 * @package OCA\Circles
 */
interface IFederatedItem {
	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 */
	public function verify(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void;

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void;
}
