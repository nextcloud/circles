<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events\Files;

use OCA\Circles\Events\CircleResultGenericEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Tools\Model\SimpleDataStore;

/**
 * Class CreatingFileShareEvent
 *
 * @package OCA\Circles\Events\Files
 */
class FileShareCreatedEvent extends CircleResultGenericEvent {
	/**
	 * FileShareCreatedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 * @param SimpleDataStore[] $result
	 */
	public function __construct(FederatedEvent $federatedEvent, array $result) {
		parent::__construct($federatedEvent, $result);
	}
}
